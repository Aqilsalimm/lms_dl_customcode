<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\UsersExport;
use Maatwebsite\Excel\Facades\Excel;

class UserManageController extends Controller
{
    /**
     * Display the User Manage dashboard.
     */
    public function index()
    {
        $currentUser = auth()->user();
        if (!$currentUser || !$currentUser->isAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $users = User::with('instructorProfile')->latest()->get();
        $pendingInstructors = User::where('role', 'instructor')
                                  ->where('status', 'pending')
                                  ->with('instructorProfile')
                                  ->latest()
                                  ->get();

        $revenueShareSetting = Setting::where('key', 'instructor_revenue_share')->first();
        $revenueShare = $revenueShareSetting ? $revenueShareSetting->value : '70'; // Default 70% if not set

        return Inertia::render('Dashboard/Admin/UserManage', [
            'users' => $users,
            'pendingInstructors' => $pendingInstructors,
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
}
