<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\QuizAttempt;
use App\Models\Review;
use App\Models\StudentLearningLog;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StudentDashboardController extends Controller
{
    /**
     * Student Dashboard Data & View
     */
    public function index(): Response
    {
        $user = auth()->user();

        // Enrolled courses with modules and lessons count (eager loaded)
        $enrollments = Enrollment::where('user_id', $user->id)
            ->whereHas('course')
            ->with(['course' => function ($query) {
                $query->withCount(['modules', 'lessons'])->with(['instructor', 'category']);
            }])
            ->whereNotNull('course_id')
            ->get();

        $coursesList = $enrollments->map(function ($enrollment) {
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
        })->filter()->values();

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
     * Student Learning Progress Telemetry page (O(1) database queries, side-effect free)
     */
    public function learningProgress(): Response
    {
        $user = auth()->user();

        // Fetch Telemetry Data
        $latestProgress = StudentLearningLog::where('user_id', $user->id)
            ->with(['course', 'lesson'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'course_title' => $log->course->title ?? 'Kelas',
                    'lesson_title' => $log->lesson->title ?? 'Sesi Belajar',
                    'activity_type' => $log->activity_type,
                    'watch_minutes' => (int) round($log->watch_seconds / 60),
                    'created_at_formatted' => $log->created_at->diffForHumans(),
                ];
            });

        $topicStats = StudentLearningLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select('topic_name', DB::raw('SUM(watch_seconds) as total_seconds'))
            ->groupBy('topic_name')
            ->get();

        $totalSecondsSum = $topicStats->sum('total_seconds') ?: 1;

        $mostTopics = $topicStats->map(function ($stat) use ($totalSecondsSum) {
            return [
                'topic' => $stat->topic_name ?: 'Lainnya',
                'percentage' => (int) round(($stat->total_seconds / $totalSecondsSum) * 100),
                'minutes' => (int) round($stat->total_seconds / 60),
            ];
        })->sortByDesc('percentage')->values();

        // Single batch aggregate query for 7-day watch time
        $sevenDaysAgo = now()->subDays(6)->startOfDay();
        $logsByDate = StudentLearningLog::where('user_id', $user->id)
            ->where('created_at', '>=', $sevenDaysAgo)
            ->selectRaw('DATE(created_at) as log_date, SUM(watch_seconds) as total_seconds')
            ->groupBy('log_date')
            ->pluck('total_seconds', 'log_date');

        $dailyWatchTime = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->toDateString();
            $seconds = (int) ($logsByDate[$dateString] ?? 0);

            $dailyWatchTime[] = [
                'date' => $date->format('d M'),
                'minutes' => (int) round($seconds / 60),
            ];
        }

        return Inertia::render('Dashboard/Student/LearningProgress', [
            'telemetry' => [
                'latest_progress' => $latestProgress,
                'most_topics' => $mostTopics,
                'watch_time' => $dailyWatchTime,
            ]
        ]);
    }

    /**
     * Display Enrolled Courses page
     */
    public function enrolledCourses(): Response
    {
        $user = auth()->user();

        // Fetch course IDs from user's actual enrollments
        $enrolledCourseIds = Enrollment::where('user_id', $user->id)
            ->whereNotNull('course_id')
            ->pluck('course_id')
            ->toArray();

        $query = Course::query()
            ->withCount(['modules', 'lessons'])
            ->with('instructor');

        if ($user->isAdmin()) {
            // Superadmin has full access to all courses in the enrolled view
        } elseif ($user->isInstructor()) {
            // Instructor sees their authored courses + enrolled courses
            $query->where(function ($q) use ($user, $enrolledCourseIds) {
                $q->where('instructor_id', $user->id)
                  ->orWhereIn('id', $enrolledCourseIds);
            });
        } else {
            // Normal student sees only enrolled courses
            $query->whereIn('id', $enrolledCourseIds);
        }

        $courses = $query->get();

        $coursesList = $courses->map(function ($course) {
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
                'status' => 'enrolled',
                'course_type' => $course->course_type ?? 'async',
                'start_date' => $course->start_date ? $course->start_date->toISOString() : null,
                'end_date' => $course->end_date ? $course->end_date->toISOString() : null,
                'meeting_url' => $course->meeting_url,
            ];
        })->values();

        return Inertia::render('Dashboard/Student/EnrolledCourses', [
            'enrolledCourses' => $coursesList
        ]);
    }

    /**
     * Display Student's Reviews page
     */
    public function reviews(): Response
    {
        $user = auth()->user();

        $reviews = Review::with('course')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        $enrolledCourseIds = Enrollment::where('user_id', $user->id)
            ->where('status', 'active')
            ->pluck('course_id');

        $coursesToReview = Course::whereIn('id', $enrolledCourseIds)->get();

        return Inertia::render('Dashboard/Student/Reviews', [
            'reviews' => $reviews,
            'coursesToReview' => $coursesToReview
        ]);
    }

    /**
     * Display Student's Wishlist page
     */
    public function wishlist(): Response
    {
        $user = auth()->user();

        $wishlistItems = Wishlist::with(['course.instructor', 'course.category'])
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard/Student/Wishlist', [
            'wishlistItems' => $wishlistItems
        ]);
    }

    /**
     * Display Student's Order History page
     */
    public function orderHistory(): Response
    {
        $user = auth()->user();

        $orders = Order::with('buyable')
            ->where('user_id', $user->id)
            ->latest()
            ->get();

        return Inertia::render('Dashboard/Student/OrderHistory', [
            'orders' => $orders
        ]);
    }

    /**
     * Placeholder method for unimplemented routes
     */
    public function placeholder()
    {
        return redirect()->route('dashboard')->with('info', 'This feature is currently under development.');
    }
}
