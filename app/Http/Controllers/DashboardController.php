<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Standard dashboard entry point
     */
    public function index()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard();
        } elseif ($user->isInstructor()) {
            return $this->instructorDashboard();
        } else {
            return $this->studentDashboard();
        }
    }

    /**
     * Admin Dashboard Data & View
     */
    private function adminDashboard()
    {
        // 1. Core Metrics
        $totalRevenue = Order::where('status', 'completed')->sum('amount');
        $totalStudents = User::where('role', 'student')->count();
        $totalInstructors = User::where('role', 'instructor')->count();
        $totalCourses = Course::count();

        // 2. Recent Transactions
        $recentTransactions = Order::with('user', 'buyable')
            ->latest()
            ->limit(5)
            ->get();

        // 3. Monthly Revenue Chart Data
        $monthlyRevenue = Order::where('status', 'completed')
            ->select(
                DB::raw('SUM(amount) as sum'),
                DB::raw("DATE_FORMAT(created_at, '%M') as month")
            )
            ->groupBy('month')
            ->orderBy(DB::raw('MIN(created_at)'))
            ->get();

        // 4. Recent Instructors and Students
        $recentUsers = User::latest()->limit(5)->get();

        return Inertia::render('Dashboard/Admin/Index', [
            'metrics' => [
                'total_revenue' => 'Rp ' . number_format($totalRevenue, 0, ',', '.'),
                'total_students' => $totalStudents,
                'total_instructors' => $totalInstructors,
                'total_courses' => $totalCourses,
            ],
            'recentTransactions' => $recentTransactions,
            'monthlyRevenue' => $monthlyRevenue,
            'recentUsers' => $recentUsers,
        ]);
    }

    /**
     * Instructor Dashboard Data & View
     */
    private function instructorDashboard()
    {
        $user = auth()->user();

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
        $instructorEarnings = $grossRevenue * 0.70; // 70% share

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
     * Student Dashboard Data & View
     */
    private function studentDashboard()
    {
        $user = auth()->user();

        // Enrolled courses with modules and lessons count
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with(['course' => function($query) {
                $query->withCount(['modules', 'lessons'])->with('instructor');
            }])
            ->whereNotNull('course_id')
            ->get();

        // Overall progress calculations, e.g. completed lessons
        // (For now, mock progress of 25% for visualization in dashboard, we can implement exact lesson progress tracking later)
        $coursesList = $enrollments->map(function($enrollment) {
            $course = $enrollment->course;
            if (!$course) return null;
            
            $completedCount = count($enrollment->completed_lessons ?? []);
            $totalLessonsCount = $course->lessons_count;
            $progress = $totalLessonsCount > 0 ? (int) round(($completedCount / $totalLessonsCount) * 100) : 0;

            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'level' => $course->level,
                'thumbnail' => $course->thumbnail,
                'bg_color' => $course->bg_color,
                'icon_type' => $course->icon_type,
                'instructor_name' => $course->instructor->name ?? 'Admin',
                'lessons_count' => $totalLessonsCount,
                'modules_count' => $course->modules_count,
                'progress' => $progress,
            ];
        })->filter();

        // Quiz attempts
        $quizAttempts = QuizAttempt::where('user_id', $user->id)
            ->with('quiz')
            ->latest()
            ->get();

        // Order history
        $orders = Order::where('user_id', $user->id)
            ->with('buyable')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/Student/Index', [
            'enrolledCourses' => $coursesList,
            'quizAttempts' => $quizAttempts,
            'orders' => $orders,
            'metrics' => [
                'enrolled_count' => $enrollments->count(),
                'completed_quizzes' => $quizAttempts->count(),
                'passed_quizzes' => $quizAttempts->where('score', '>=', 75.00)->count(),
            ]
        ]);
    }

    /**
     * Admin/Instructor action to promote/change user roles
     */
    public function changeRole(Request $request, User $user)
    {
        $this->authorize('isAdmin', auth()->user());

        $request->validate([
            'role' => 'required|string|in:admin,instructor,student',
        ]);

        $user->update(['role' => $request->role]);

        return back()->with('success', 'User role updated successfully');
    }

    /**
     * Display Enrolled Courses page
     */
    public function enrolledCourses()
    {
        $user = auth()->user();

        // Enrolled courses with modules and lessons count
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with(['course' => function($query) {
                $query->withCount(['modules', 'lessons'])->with('instructor');
            }])
            ->whereNotNull('course_id')
            ->get();

        $coursesList = $enrollments->map(function($enrollment) {
            $course = $enrollment->course;
            if (!$course) return null;
            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'level' => $course->level,
                'thumbnail' => $course->thumbnail,
                'bg_color' => $course->bg_color,
                'icon_type' => $course->icon_type,
                'instructor_name' => $course->instructor->name ?? 'Admin',
                'lessons_count' => $course->lessons_count,
                'modules_count' => $course->modules_count,
                'status' => 'enrolled', // We'll add completed logic later if needed
            ];
        })->filter()->values();

        return Inertia::render('Dashboard/Student/EnrolledCourses', [
            'enrolledCourses' => $coursesList
        ]);
    }

    /**
     * Display LMS Settings page (Admin only)
     */
    public function settings()
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $settings = \App\Models\Setting::pluck('value', 'key')->toArray();

        return Inertia::render('Dashboard/Admin/Settings', [
            'settings' => $settings
        ]);
    }

    /**
     * Update LMS Settings
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $settingsData = $request->input('settings', []);
        
        foreach ($settingsData as $key => $value) {
            \App\Models\Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    /**
     * Placeholder method for unimplemented routes
     */
    public function placeholder()
    {
        return redirect()->route('dashboard')->with('info', 'This feature is currently under development.');
    }
}
