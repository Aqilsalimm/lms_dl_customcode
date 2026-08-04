<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyManagedUserRequest;
use App\Http\Requests\Admin\StoreManagedUserRequest;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\UsersExport;
use App\Models\Otp;
use App\Mail\OtpMail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Admin\UserManagementService;

class UserManageController extends Controller
{
    public function __construct(private readonly UserManagementService $userManagement) {}

    /**
     * Display the User Manage dashboard.
     */
    public function index()
    {
        if (\Illuminate\Support\Facades\Gate::denies('viewAny', User::class)) {
            abort(403, 'Unauthorized access.');
        }

        $userColumns = ['id', 'name', 'email', 'role', 'status', 'photo', 'created_at'];
        $profileColumns = ['id', 'user_id', 'expertise_area', 'portfolio_url', 'resume_file', 'bio_summary'];

        $users = User::query()->select($userColumns)
            ->with(['instructorProfile' => fn ($query) => $query->select($profileColumns)])
            ->latest()->get();
        $pendingInstructors = User::where('role', 'instructor')
                                  ->where('status', 'pending')
                                  ->select($userColumns)
                                  ->with(['instructorProfile' => fn ($query) => $query->select($profileColumns)])
                                  ->latest()
                                  ->get();
        $trashedUsers = User::onlyTrashed()
            ->select(['id', 'name', 'email', 'role', 'status', 'deleted_at'])
            ->latest()->get();

        $revenueShare = Setting::getValue('instructor_revenue_share', '70');

        return Inertia::render('Dashboard/Admin/UserManage', [
            'users' => $users,
            'pendingInstructors' => $pendingInstructors,
            'trashedUsers' => $trashedUsers,
            'globalRevenueShare' => $revenueShare
        ]);
    }

    /**
     * Approve a pending instructor application.
     */
    public function approveInstructor(User $user)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->role === 'instructor' && $user->status === 'pending') {
            $user->update(['status' => 'active']);
            // TODO: Dispatch Welcome Email
            // e.g., Mail::to($user->email)->send(new InstructorApprovedMail($user));
            return back()->with('success', 'Instruktur berhasil disetujui. Email selamat datang (Welcome Email) dapat dikonfigurasi untuk terkirim otomatis di titik ini.');
        }

        return back()->with('error', 'Status pengguna tidak valid untuk persetujuan.');
    }

    /**
     * Reject a pending instructor application.
     */
    public function rejectInstructor(User $user)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        if ($user->role === 'instructor' && $user->status === 'pending') {
            if ($user->instructorProfile) {
                $user->instructorProfile()->delete();
            }
            $user->update([
                'role' => 'student',
                'status' => 'active'
            ]);
            // TODO: Dispatch Rejection Email
            return back()->with('success', 'Aplikasi instruktur ditolak. Pengguna dikembalikan sebagai Siswa (Student).');
        }

        return back()->with('error', 'Status pengguna tidak valid untuk penolakan.');
    }

    /**
     * Update user role.
     */
    public function updateRole(Request $request, User $user)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        // Prevent modification of Superadmin
        if ($user->id === 1) {
            return back()->with('error', 'Role Superadmin (ID 1) tidak dapat diubah.');
        }

        $request->validate([
            'role' => 'required|string|in:admin,instructor,student',
        ]);

        $status = $user->status;
        if ($request->role === 'instructor' && $user->role !== 'instructor') {
            $status = 'active'; // Admin manually changing role automatically activates
        }

        $user->update([
            'role' => $request->role,
            'status' => $status
        ]);

        return back()->with('success', 'Role pengguna berhasil diperbarui.');
    }

    /**
     * Export all users to Excel.
     */
    public function export()
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }
        
        return Excel::download(new UsersExport, 'users_drastha_lms.xlsx');
    }

    /**
     * Store a newly created user (Admin action).
     */
    public function store(StoreManagedUserRequest $request)
    {
        $this->userManagement->create($request->validated());

        return back()->with('success', 'Pengguna berhasil ditambahkan. Tautan aktivasi telah dijadwalkan untuk dikirim.');
    }

    /**
     * Send OTP for Delete User confirmation.
     */
    public function sendDeleteOtp(Request $request)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $rateLimitKey = "admin-user-delete-otp:{$currentUser->id}";
        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            return back()->with('error', 'Terlalu banyak permintaan OTP. Coba lagi dalam '.RateLimiter::availableIn($rateLimitKey).' detik.');
        }
        RateLimiter::hit($rateLimitKey, 60);

        // Invalidate old unused OTPs
        Otp::where('user_id', $currentUser->id)
            ->where('purpose', Otp::PURPOSE_USER_DELETE)
            ->where('used', false)->update(['used' => true]);

        $code = random_int(100000, 999999);

        Otp::create([
            'user_id'    => $currentUser->id,
            'email'      => $currentUser->email,
            'otp_code'   => Hash::make((string) $code),
            'purpose'    => Otp::PURPOSE_USER_DELETE,
            'expires_at' => now()->addMinutes(10),
            'used'       => false,
        ]);

        Mail::to($currentUser->email)->queue(new OtpMail($code));

        return back()->with('success', 'Kode OTP konfirmasi hapus telah dikirim ke email Anda.');
    }

    /**
     * Soft Delete a user.
     */
    public function destroy(DestroyManagedUserRequest $request, User $user)
    {
        $this->userManagement->deleteWithOtp(
            $request->user(),
            $user,
            $request->validated('otp_code'),
        );

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(Request $request, int $id)
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $user = User::onlyTrashed()->findOrFail($id);
        Gate::authorize('restore', $user);
        $user->restore();

        return back()->with('success', 'Akun pengguna berhasil dipulihkan.');
    }
}

