<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InstructorDashboardController extends Controller
{
    /**
     * Instructor Dashboard Data & View
     */
    public function index(): Response
    {
        $user = auth()->user();

        // Check if instructor profile is pending
        if ($user->status === 'pending') {
            return Inertia::render('Dashboard/Instructor/PendingApproval', [
                'user' => $user
            ]);
        }

        // Get all courses owned by instructor
        $courses = Course::where('instructor_id', $user->id)
            ->withCount(['enrollments', 'modules', 'lessons'])
            ->get();

        $courseIds = $courses->pluck('id');

        // Total students enrolled in their courses
        $totalStudents = Enrollment::whereIn('course_id', $courseIds)
            ->distinct('user_id')
            ->count();

        // Calculate instructor earnings (e.g., 70% share of completed orders)
        $courseOrders = Order::where('buyable_type', Course::class)
            ->whereIn('buyable_id', $courseIds)
            ->where('status', 'completed')
            ->get();

        $grossRevenue = $courseOrders->sum('amount');
        $instructorEarnings = $grossRevenue * 0.70;

        // Recent enrollments in their courses
        $recentEnrollments = Enrollment::with(['user', 'course'])
            ->whereIn('course_id', $courseIds)
            ->latest()
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard/Instructor/Index', [
            'courses' => $courses,
            'metrics' => [
                'total_courses' => $courses->count(),
                'total_students' => $totalStudents,
                'gross_revenue' => 'Rp ' . number_format($grossRevenue, 0, ',', '.'),
                'instructor_earnings' => 'Rp ' . number_format($instructorEarnings, 0, ',', '.'),
            ],
            'recentEnrollments' => $recentEnrollments,
        ]);
    }

    /**
     * Display Instructor's Live Class Schedule page
     */
    public function liveClassSchedule()
    {
        $user = auth()->user();
        if (!$user->isInstructor() && !$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        // Fetch instructor's live class courses
        $courses = Course::where('course_type', 'live_class')
            ->when(!$user->isAdmin(), function ($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })
            ->get();

        return Inertia::render('Dashboard/Instructor/LiveClass', [
            'courses' => $courses
        ]);
    }

    /**
     * Update Live Class Schedule details
     */
    public function updateLiveClassSchedule(Request $request, Course $course)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $course->instructor_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'timezone' => 'nullable|string',
            'meeting_url' => 'nullable|string|url',
            'recording_url' => 'nullable|string|url',
            'max_participants' => 'nullable|integer|min:1',
            'is_event_finished' => 'nullable|boolean',
            'platform_type' => 'required|string|in:zoom,meet',
        ]);

        $course->update([
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'timezone' => $request->timezone ?: 'Asia/Jakarta',
            'meeting_url' => $request->meeting_url,
            'recording_url' => $request->recording_url,
            'max_participants' => $request->max_participants,
            'is_event_finished' => (bool) $request->is_event_finished,
        ]);

        // Also synchronize the `about` JSON field (for Course Builder integration)
        $about = [];
        if ($course->about && str_starts_with($course->about, '{') && str_ends_with($course->about, '}')) {
            try {
                $about = json_decode($course->about, true) ?: [];
            } catch (\Exception $e) {}
        } else {
            $about = ['overview' => $course->about ?: ''];
        }

        $formVal = [
            'name' => 'Kelas Live: ' . $course->title,
            'summary' => $about['overview'] ?? 'Sesi tanya jawab live interaktif mengenai materi pembelajaran.',
            'date' => $request->start_date ? substr($request->start_date, 0, 10) : date('Y-m-d'),
            'time' => $request->start_date ? substr($request->start_date, 11, 5) : '19:00',
            'duration' => 40,
            'durationUnit' => 'Minutes',
            'timezone' => $request->timezone ?: 'Asia/Jakarta',
            'link' => $request->meeting_url ?: '',
            'meetingId' => '',
            'password' => '',
        ];

        if ($request->platform_type === 'zoom') {
            $about['live_zoom_link'] = $request->meeting_url;
            $about['live_zoom_data'] = $formVal;
            $about['live_gmeet_link'] = '';
            $about['live_gmeet_data'] = null;
        } else {
            $about['live_gmeet_link'] = $request->meeting_url;
            $about['live_gmeet_data'] = $formVal;
            $about['live_zoom_link'] = '';
            $about['live_zoom_data'] = null;
        }

        $about['live_class_reminder_sent'] = false;

        $course->update([
            'about' => json_encode($about)
        ]);

        return redirect()->back()->with('success', 'Jadwal kelas live berhasil diperbarui!');
    }
}
