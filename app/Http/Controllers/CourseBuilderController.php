<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CoursesImport;

class CourseBuilderController extends Controller
{
    /**
     * View for Course Builder Index (renders with Inertia)
     */
    public function index()
    {
        $user = auth()->user();
        
        $courses = Course::with(['category', 'lessons'])
            ->when(!$user->isAdmin(), function($query) use ($user) {
                return $query->where('instructor_id', $user->id);
            })
            ->latest()
            ->get();

        return Inertia::render('Dashboard/Instructor/CourseIndex', [
            'courses' => $courses
        ]);
    }

    /**
     * Load metadata for creating/editing courses
     */
    public function getMetadata()
    {
        return response()->json([
            'categories' => Category::all(),
            'tags' => Tag::all(),
        ]);
    }

    /**
     * Store new course
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'level' => 'required|string|in:SD,SMP,SMA,Umum',
        ]);

        $course = Course::create([
            'instructor_id' => auth()->id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'price' => $request->price,
            'level' => $request->level,
            'status' => 'draft',
            'icon_type' => 'code',
            'bg_color' => '#44A6D9',
        ]);

        if ($request->has('tags')) {
            $course->tags()->sync($request->tags);
        }

        return response()->json([
            'message' => 'Course created successfully',
            'course' => $course
        ], 21);
    }

    /**
     * Render the Course Builder page for a specific course
     */
    public function builder(Course $course)
    {
        // Authorize
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            abort(403);
        }

        $course->load([
            'category', 
            'tags', 
            'modules.lessons', 
            'modules.quizzes.questions'
        ]);

        return Inertia::render('Dashboard/Instructor/CourseBuilder', [
            'course' => $course,
            'categories' => Category::all(),
            'tags' => Tag::all(),
            'courses' => Course::where('id', '!=', $course->id)->get(['id', 'title', 'thumbnail'])
        ]);
    }

    /**
     * Update course main details
     */
    public function update(Request $request, Course $course)
    {
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'level' => 'required|string|in:SD,SMP,SMA,Umum',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|string|in:draft,published',
            'description' => 'nullable|string',
            'about' => 'nullable|string',
            'thumbnail' => 'nullable',
            'bg_color' => 'nullable|string',
            'icon_type' => 'nullable|string',
        ]);

        $data = $request->only([
            'title', 'category_id', 'price', 'level', 'capacity', 'status',
            'description', 'about', 'bg_color', 'icon_type'
        ]);

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            $data['thumbnail'] = $path;
        } elseif ($request->has('thumbnail')) {
            $data['thumbnail'] = $request->thumbnail;
        }

        $course->update($data);

        if ($request->has('tags')) {
            $course->tags()->sync($request->tags);
        }

        return response()->json([
            'message' => 'Course updated successfully',
            'course' => $course
        ]);
    }

    /**
     * Delete course
     */
    public function destroy(Course $course)
    {
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $course->delete();

        return response()->json(['message' => 'Course deleted successfully']);
    }

    /**
     * Import courses from CSV/Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt,xlsx,xls|max:10240', // max 10MB
        ]);

        try {
            Excel::import(new CoursesImport, $request->file('file'));
            
            // Return back with Inertia flash message
            return back()->with('message', 'Kursus berhasil di-import dari file Excel/CSV!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengimport: ' . $e->getMessage());
        }
    }

    // --- MODULES (SECTIONS) MANAGEMENT ---

    public function addModule(Request $request, Course $course)
    {
        $request->validate(['title' => 'required|string|max:255']);

        $sortOrder = $course->modules()->count();

        $module = Module::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'sort_order' => $sortOrder
        ]);

        return response()->json([
            'message' => 'Module added successfully',
            'module' => $module
        ]);
    }

    public function updateModule(Request $request, Module $module)
    {
        $request->validate(['title' => 'required|string|max:255']);
        $module->update(['title' => $request->title]);

        return response()->json([
            'message' => 'Module updated successfully',
            'module' => $module
        ]);
    }

    public function deleteModule(Module $module)
    {
        $module->delete();
        return response()->json(['message' => 'Module deleted successfully']);
    }

    // --- LESSONS MANAGEMENT ---

    public function addLesson(Request $request, Module $module)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|url',
            'duration_minutes' => 'required|integer|min:0',
        ]);

        $sortOrder = $module->lessons()->count();

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'duration_minutes' => $request->duration_minutes,
            'sort_order' => $sortOrder
        ]);

        return response()->json([
            'message' => 'Lesson added successfully',
            'lesson' => $lesson
        ]);
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|url',
            'duration_minutes' => 'required|integer|min:0',
        ]);

        $lesson->update($request->only(['title', 'content', 'video_url', 'duration_minutes']));

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson
        ]);
    }

    public function deleteLesson(Lesson $lesson)
    {
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted successfully']);
    }

    // --- QUIZZES MANAGEMENT ---

    public function addQuiz(Request $request, Module $module)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit_minutes' => 'required|integer|min:0',
        ]);

        $sortOrder = $module->quizzes()->count();

        $quiz = Quiz::create([
            'module_id' => $module->id,
            'title' => $request->title,
            'description' => $request->description,
            'time_limit_minutes' => $request->time_limit_minutes,
            'sort_order' => $sortOrder
        ]);

        return response()->json([
            'message' => 'Quiz added successfully',
            'quiz' => $quiz->load('questions')
        ]);
    }

    public function updateQuiz(Request $request, Quiz $quiz)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'time_limit_minutes' => 'required|integer|min:0',
        ]);

        $quiz->update($request->only(['title', 'description', 'time_limit_minutes']));

        return response()->json([
            'message' => 'Quiz updated successfully',
            'quiz' => $quiz->load('questions')
        ]);
    }

    public function deleteQuiz(Quiz $quiz)
    {
        $quiz->delete();
        return response()->json(['message' => 'Quiz deleted successfully']);
    }

    // --- QUIZ QUESTIONS MANAGEMENT ---

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array',
            'correct_option_index' => 'required|integer',
        ]);

        $sortOrder = $quiz->questions()->count();

        $question = QuizQuestion::create([
            'quiz_id' => $quiz->id,
            'question_text' => $request->question_text,
            'options' => $request->options,
            'correct_option_index' => $request->correct_option_index,
            'sort_order' => $sortOrder
        ]);

        return response()->json([
            'message' => 'Question added successfully',
            'question' => $question
        ]);
    }

    public function updateQuestion(Request $request, QuizQuestion $question)
    {
        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array',
            'correct_option_index' => 'required|integer',
        ]);

        $question->update($request->only(['question_text', 'options', 'correct_option_index']));

        return response()->json([
            'message' => 'Question updated successfully',
            'question' => $question
        ]);
    }

    public function deleteQuestion(QuizQuestion $question)
    {
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }
}
