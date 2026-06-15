<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Display a listing of public courses
     */
    public function index(Request $request)
    {
        // Check Course Visibility Setting
        $visibility = \App\Models\Setting::where('key', 'course_visibility')->value('value');
        if (filter_var($visibility, FILTER_VALIDATE_BOOLEAN) && !auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to view courses.');
        }

        $query = Course::where('status', 'published')->with(['category', 'instructor']);

        // Filter by Level (SD, SMP, SMA, Umum)
        if ($request->has('level') && $request->level !== 'Semua Kursus') {
            // Mapping friendly UI names to DB level column
            $levelMap = [
                'Kelas SD' => 'SD',
                'Kelas SMP' => 'SMP',
                'Kelas SMA' => 'SMA',
                'Umum / Profesional' => 'Umum'
            ];
            $dbLevel = $levelMap[$request->level] ?? $request->level;
            $query->where('level', $dbLevel);
        }

        // Filter by Search Query
        if ($request->has('search') && !empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Filter by Category Slug
        if ($request->has('category') && !empty($request->category)) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        $perPage = (int) (\App\Models\Setting::where('key', 'courses_per_page')->value('value') ?: 12);
        $courses = $query->latest()->paginate($perPage)->withQueryString();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'filters' => $request->only(['level', 'search', 'category']),
            'categories' => Category::all()
        ]);
    }

    /**
     * Display the course details page
     */
    public function show(string $slug)
    {
        // Check Course Visibility Setting
        $visibility = \App\Models\Setting::where('key', 'course_visibility')->value('value');
        if (filter_var($visibility, FILTER_VALIDATE_BOOLEAN) && !auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to view course details.');
        }

        $course = Course::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'tags', 'instructor', 'modules.lessons', 'modules.quizzes.questions'])
            ->firstOrFail();

        // Check if current user is enrolled
        $isEnrolled = false;
        if (auth()->check()) {
            $isEnrolled = auth()->user()->hasEnrolled($course->id);
        }

        $contentSummary = filter_var(
            \App\Models\Setting::where('key', 'content_summary')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled,
            'showContentSummary' => $contentSummary
        ]);
    }

    /**
     * Display the course classroom learn page
     */
    public function learn(string $slug)
    {
        // 1. Authenticate check
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Fetch course with published modules & lessons
        $course = Course::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'tags', 'instructor', 'modules.lessons', 'modules.quizzes.questions'])
            ->firstOrFail();

        // 3. Authorization check (is Enrolled or is Instructor of course or is Admin)
        $user = auth()->user();
        $isAuthor = $course->instructor_id === $user->id;
        
        $allowAccessWithoutEnroll = filter_var(
            \App\Models\Setting::where('key', 'course_content_access')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );

        $isAuthorized = $user->hasEnrolled($course->id) || 
            $isAuthor || 
            ($allowAccessWithoutEnroll && ($user->isAdmin() || $user->isInstructor())) ||
            (!$allowAccessWithoutEnroll && $user->isAdmin());

        if (!$isAuthorized) {
            return redirect()->route('courses.show', $course->slug)
                ->with('warning', 'Silakan daftar di kelas ini terlebih dahulu untuk memulai belajar.');
        }

        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $completedLessons = $enrollment ? ($enrollment->completed_lessons ?? []) : [];
        $completedQuizzes = $enrollment ? ($enrollment->completed_quizzes ?? []) : [];
        $completedAt = $enrollment ? $enrollment->completed_at : null;

        $spotlightMode = filter_var(
            \App\Models\Setting::where('key', 'spotlight_mode')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );

        return Inertia::render('Courses/Learn', [
            'course' => $course,
            'spotlightMode' => $spotlightMode,
            'dbCompletedLessons' => $completedLessons,
            'dbCompletedQuizzes' => $completedQuizzes,
            'dbCompletedAt' => $completedAt,
        ]);
    }

    /**
     * Toggle completion of a lesson for the current user.
     */
    public function toggleLessonComplete(string $slug, string $lessonId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = auth()->user();

        // Get the user's enrollment
        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $completedLessons = $enrollment->completed_lessons ?? [];
        
        $lessonIdInt = (int) $lessonId;
        if (in_array($lessonIdInt, $completedLessons)) {
            // Remove lesson ID
            $completedLessons = array_values(array_filter($completedLessons, function($id) use ($lessonIdInt) {
                return $id !== $lessonIdInt;
            }));
        } else {
            // Add lesson ID
            $completedLessons[] = $lessonIdInt;

            // Log completion in student_learning_logs
            \App\Models\StudentLearningLog::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'lesson_id' => $lessonIdInt,
                'activity_type' => 'lesson_complete',
                'watch_seconds' => 0,
                'topic_name' => $course->category->name ?? $course->title
            ]);
        }

        $enrollment->completed_lessons = $completedLessons;

        // Check if all lessons AND quizzes of the course are completed
        $totalLessonsCount = $course->lessons()->count();
        $totalQuizzesCount = \App\Models\Quiz::whereIn('module_id', $course->modules()->pluck('id'))->count();
        $completedQuizzes = $enrollment->completed_quizzes ?? [];

        if (count($completedLessons) >= $totalLessonsCount && count($completedQuizzes) >= $totalQuizzesCount) {
            $enrollment->completed_at = now();
        } else {
            $enrollment->completed_at = null;
        }

        $enrollment->save();

        return response()->json([
            'completedLessons' => $completedLessons,
            'completedAt' => $enrollment->completed_at,
        ]);
    }

    /**
     * Mark a quiz as passed/completed for the current user.
     */
    public function toggleQuizComplete(Request $request, string $slug, string $quizId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = auth()->user();

        // Get the user's enrollment
        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $completedQuizzes = $enrollment->completed_quizzes ?? [];
        
        $quizIdInt = (int) $quizId;
        if (!in_array($quizIdInt, $completedQuizzes)) {
            $completedQuizzes[] = $quizIdInt;
        }

        $enrollment->completed_quizzes = $completedQuizzes;

        // Check if all lessons AND quizzes are completed
        $totalLessonsCount = $course->lessons()->count();
        $totalQuizzesCount = \App\Models\Quiz::whereIn('module_id', $course->modules()->pluck('id'))->count();
        $completedLessons = $enrollment->completed_lessons ?? [];

        if (count($completedLessons) >= $totalLessonsCount && count($completedQuizzes) >= $totalQuizzesCount) {
            $enrollment->completed_at = now();
        } else {
            $enrollment->completed_at = null;
        }

        $enrollment->save();

        return response()->json([
            'completedQuizzes' => $completedQuizzes,
            'completedAt' => $enrollment->completed_at,
        ]);
    }

    /**
     * Log student learning progress (video play duration or lesson viewing)
     */
    public function logProgress(Request $request, string $slug, string $lessonId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = auth()->user();
        
        $request->validate([
            'watch_seconds' => 'required|integer',
            'activity_type' => 'required|string'
        ]);

        $lesson = \App\Models\Lesson::findOrFail($lessonId);

        // Store log
        $log = \App\Models\StudentLearningLog::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'activity_type' => $request->activity_type,
            'watch_seconds' => $request->watch_seconds,
            'topic_name' => $course->category->name ?? $course->title
        ]);

        return response()->json(['success' => true]);
    }


    /**
     * View/Print course certificate
     */
    public function certificate(string $slug)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $course = Course::where('slug', $slug)
            ->where('status', 'published')
            ->with(['instructor'])
            ->firstOrFail();

        $user = auth()->user();
        $isAuthor = $course->instructor_id === $user->id;

        // Check if enrolled and completed
        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $totalLessonsCount = $course->lessons()->count();
        $totalQuizzesCount = \App\Models\Quiz::whereIn('module_id', $course->modules()->pluck('id'))->count();

        // Safe fallback: completed if explicitly marked or completed lessons + quizzes are fully completed
        $isCompleted = ($enrollment && $enrollment->completed_at !== null) || 
                       ($enrollment && 
                        count($enrollment->completed_lessons ?? []) >= $totalLessonsCount && 
                        count($enrollment->completed_quizzes ?? []) >= $totalQuizzesCount);

        // Admins and instructors can always preview
        $canAccess = $user->isAdmin() || $isAuthor || $isCompleted;

        if (!$canAccess) {
            return redirect()->route('courses.show', $course->slug)
                ->with('warning', 'Sertifikat belum tersedia. Silakan selesaikan seluruh materi pembelajaran terlebih dahulu.');
        }

        // Get certificate specific settings keys
        $settings = [
            'cert_authorised_name' => \App\Models\Setting::where('key', 'cert_authorised_name')->value('value') ?: 'John Doe',
            'cert_company_name' => \App\Models\Setting::where('key', 'cert_company_name')->value('value') ?: 'Drastha Learning Inc.',
            'cert_page' => \App\Models\Setting::where('key', 'cert_page')->value('value') ?: 'certificate',
            'cert_signature' => \App\Models\Setting::where('key', 'cert_signature')->value('value') ?: '/images/signature-placeholder.png',
            'cert_show_instructor' => filter_var(\App\Models\Setting::where('key', 'cert_show_instructor')->value('value') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('Courses/Certificate', [
            'course' => $course,
            'settings' => $settings,
            'completedAt' => $enrollment ? $enrollment->completed_at : now()->toDateTimeString(),
            'studentName' => $user->name,
        ]);
    }
}
