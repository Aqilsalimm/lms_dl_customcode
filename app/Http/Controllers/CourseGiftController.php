<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Subscription;
use App\Models\User;
use App\Mail\CourseGiftedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CourseGiftController extends Controller
{
    /**
     * Search students for autocomplete dropdown
     */
    public function searchStudents(Request $request)
    {
        $q = $request->input('q');

        if (empty($q)) {
            return response()->json([]);
        }

        $students = User::where('role', 'student')
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            })
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($students);
    }

    /**
     * Gift a course to a student
     */
    public function gift(Request $request, Course $course)
    {
        // Authorize: Admin or Instructor who owns the course
        $currentUser = auth()->user();
        if (!$currentUser->isAdmin() && $course->instructor_id !== $currentUser->id) {
            abort(403, 'Anda tidak memiliki otorisasi untuk memberikan akses kelas ini.');
        }

        $request->validate([
            'student_id' => 'required|exists:users,id',
        ]);

        $studentId = $request->input('student_id');
        $student = User::findOrFail($studentId);

        // Ensure user is not already enrolled/subscribed
        $hasActiveSubscription = Subscription::where('user_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        $hasActiveEnrollment = Enrollment::where('user_id', $studentId)
            ->where('course_id', $course->id)
            ->where('status', 'active')
            ->exists();

        if ($hasActiveSubscription || $hasActiveEnrollment) {
            return back()->with('error', 'Siswa tersebut sudah terdaftar secara aktif di kelas ini.');
        }

        // Determine billing date: set to next month if subscription-based, otherwise null
        $nextBillingDate = null;
        if ($course->payment_type === 'monthly') {
            $nextBillingDate = now()->addMonth();
        }

        DB::transaction(function () use ($student, $course, $currentUser, $nextBillingDate) {
            // Create Subscription
            Subscription::create([
                'user_id' => $student->id,
                'course_id' => $course->id,
                'status' => 'active',
                'start_date' => now(),
                'next_billing_date' => $nextBillingDate,
                'is_gifted' => true,
                'gifted_by' => $currentUser->name,
            ]);

            // Create Enrollment (grant actual learning access)
            Enrollment::firstOrCreate([
                'user_id' => $student->id,
                'course_id' => $course->id,
            ], [
                'status' => 'active',
                'enrolled_at' => now(),
            ]);
        });

        // Send Notification Email
        try {
            Mail::to($student->email)->send(new CourseGiftedMail($student, $course, $currentUser->name));
        } catch (\Exception $e) {
            logger()->error('Gagal mengirimkan email CourseGiftedMail: ' . $e->getMessage());
            // Continue even if mail delivery fails so enrollment is not reverted
        }

        return back()->with('success', "Kelas \"{$course->title}\" berhasil dihadiahkan kepada {$student->name}.");
    }
}
