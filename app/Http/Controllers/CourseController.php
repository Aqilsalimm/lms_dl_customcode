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

        $courses = $query->latest()->get();

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
        $course = Course::where('slug', $slug)
            ->where('status', 'published')
            ->with(['category', 'tags', 'instructor', 'modules.lessons', 'modules.quizzes.questions'])
            ->firstOrFail();

        // Check if current user is enrolled
        $isEnrolled = false;
        if (auth()->check()) {
            $isEnrolled = auth()->user()->hasEnrolled($course->id);
        }

        return Inertia::render('Courses/Show', [
            'course' => $course,
            'isEnrolled' => $isEnrolled
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
        $isAuthorized = $user->isAdmin() || $isAuthor || $user->hasEnrolled($course->id);

        if (!$isAuthorized) {
            return redirect()->route('courses.show', $course->slug)
                ->with('warning', 'Silakan daftar di kelas ini terlebih dahulu untuk memulai belajar.');
        }

        return Inertia::render('Courses/Learn', [
            'course' => $course,
        ]);
    }
}
