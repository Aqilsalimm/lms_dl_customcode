<?php

/**
 * ==================================================================================
 * DRASTHA LEARNING LMS - COURSE BUILDER CONTROLLER
 * ==================================================================================
 * 
 * Role & Responsibilities:
 * - Directs REST operations for modules, lessons, quizzes, and questions.
 * - Handles Google presentation and Canva embeds parsing.
 * - Validates administrative restrictions for native PPT features.
 * 
 * Maintenance Notes:
 * - Sync properties on lesson/quiz edit payloads carefully with step 2 of CourseBuilder.vue.
 * - Modifying route signatures requires updates to Vue Axios references.
 * 
 * @package App\Http\Controllers
 * @author Senior Fullstack Developer
 */

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
            'course_type' => 'required|string|in:async,live_class',
            'delivery_mode' => 'nullable|string|in:online,offline',
            'location_venue' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'meeting_url' => 'nullable|string',
        ]);

        $deliveryMode = $request->input('delivery_mode', 'online');
        $meetingUrl = $deliveryMode === 'offline' ? null : $request->meeting_url;
        $locationVenue = $deliveryMode === 'offline' ? $request->location_venue : null;

        $moderationEnabled = \App\Models\Setting::where('key', 'instructor_course_moderation')->value('value');
        $isModerated = ($moderationEnabled === 'true' || $moderationEnabled === '1' || $moderationEnabled === true || $moderationEnabled === 1);
        $initialStatus = $request->input('status', (auth()->user()->isAdmin() || !$isModerated) ? 'published' : 'draft');

        $course = Course::create([
            'instructor_id' => auth()->id(),
            'category_id' => $request->category_id,
            'title' => $request->title,
            'price' => $request->price,
            'level' => $request->level,
            'course_type' => $request->course_type,
            'delivery_mode' => $deliveryMode,
            'location_venue' => $locationVenue,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'meeting_url' => $meetingUrl,
            'status' => $initialStatus,
            'icon_type' => 'code',
            'bg_color' => '#44A6D9',
        ]);

        \Illuminate\Support\Facades\Cache::flush();

        if ($request->course_type === 'live_class') {
            \App\Models\LiveClass::create([
                'course_id' => $course->id,
                'title' => $course->title,
                'delivery_mode' => $deliveryMode,
                'meeting_link' => $meetingUrl,
                'location_venue' => $locationVenue,
                'start_time' => $request->start_date,
                'end_time' => $request->end_date,
            ]);
        }

        if ($request->has('tags')) {
            $course->tags()->sync($request->tags);
        }

        return redirect()->route('course-builder.build', $course->id)
            ->with('success', 'Course created successfully');
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

        if ($course->course_type === 'async') {
            $course->load([
                'category', 
                'tags', 
                'modules.lessons', 
                'modules.quizzes.questions'
            ]);
        } else {
            $course->load([
                'category', 
                'tags', 
                'modules.lessons',
                'modules.assessments.questions',
                'assessments.questions'
            ]);
        }

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

        // Intercept published status for instructors if course moderation is enabled
        if (!auth()->user()->isAdmin()) {
            $moderationEnabled = \App\Models\Setting::where('key', 'instructor_course_moderation')->value('value');
            $isModerated = ($moderationEnabled === 'true' || $moderationEnabled === '1' || $moderationEnabled === true || $moderationEnabled === 1);
            if ($isModerated && $request->status === 'published') {
                $request->merge(['status' => 'pending']);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|min:0',
            'payment_type' => 'nullable|string|in:one-time,monthly',
            'access_duration_months' => 'nullable|integer|min:0',
            'level' => 'required|string|in:SD,SMP,SMA,Umum',
            'capacity' => 'nullable|integer|min:1',
            'status' => 'required|string|in:draft,published,pending',
            'description' => 'nullable|string',
            'about' => 'nullable|string',
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'bg_color' => 'nullable|string',
            'icon_type' => 'nullable|string',
            'course_type' => 'nullable|string|in:async,live_class',
            'delivery_mode' => 'nullable|string|in:online,offline',
            'location_venue' => 'nullable|string',
            'documentation_urls' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'timezone' => 'nullable|string',
            'meeting_url' => 'nullable|string',
            'recording_url' => 'nullable|string',
            'max_participants' => 'nullable|integer|min:1',
            'is_event_finished' => 'nullable|boolean',
            'tools' => 'nullable|array',
        ]);

        $data = $request->only([
            'title', 'category_id', 'price', 'payment_type', 'access_duration_months', 'level', 'capacity', 'status',
            'description', 'about', 'bg_color', 'icon_type', 'course_type', 'delivery_mode', 'location_venue', 'documentation_urls', 'start_date', 'end_date',
            'timezone', 'meeting_url', 'recording_url', 'max_participants', 'is_event_finished', 'tools'
        ]);

        if (isset($data['delivery_mode']) && $data['delivery_mode'] === 'offline') {
            $data['meeting_url'] = null;
        } elseif (isset($data['delivery_mode']) && $data['delivery_mode'] === 'online') {
            $data['location_venue'] = null;
        }

        // Convert string representations of true/false to boolean
        if (isset($data['is_event_finished'])) {
            $data['is_event_finished'] = filter_var($data['is_event_finished'], FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $path),
                $request->file('thumbnail')->getMimeType()
            );
            $data['thumbnail'] = $path;
        } elseif ($request->has('thumbnail')) {
            $data['thumbnail'] = $request->thumbnail;
        }
        if (!$request->has('tools')) {
            $data['tools'] = [];
        }

        $course->update($data);

        // Synchronize or create primary LiveClass record for live classes
        if ($course->course_type === 'live_class') {
            $liveClass = \App\Models\LiveClass::where('course_id', $course->id)->first();
            if ($liveClass) {
                $liveClass->update([
                    'title' => $course->title,
                    'delivery_mode' => $course->delivery_mode ?? 'online',
                    'meeting_link' => $course->meeting_url,
                    'location_venue' => $course->location_venue,
                    'recording_url' => $course->recording_url,
                    'documentation_urls' => $course->documentation_urls,
                    'start_time' => $course->start_date,
                    'end_time' => $course->end_date,
                ]);
            } else {
                \App\Models\LiveClass::create([
                    'course_id' => $course->id,
                    'title' => $course->title,
                    'delivery_mode' => $course->delivery_mode ?? 'online',
                    'meeting_link' => $course->meeting_url,
                    'location_venue' => $course->location_venue,
                    'recording_url' => $course->recording_url,
                    'documentation_urls' => $course->documentation_urls,
                    'start_time' => $course->start_date,
                    'end_time' => $course->end_date,
                ]);
            }
        }

        if ($request->has('tags')) {
            $course->tags()->sync($request->tags);
        } else {
            $course->tags()->sync([]);
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
            abort(403);
        }

        $course->delete();

        return redirect()->back()->with('success', 'Course deleted successfully');
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
    
    private function validateCourseOwner(Course $course)
    {
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            abort(403, 'Unauthorized course access');
        }
    }

    public function addModule(Request $request, Course $course)
    {
        $this->validateCourseOwner($course);
        $request->validate([
            'title' => 'required|string|max:255',
            'meeting_url' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'recording_url' => 'nullable|string',
            'material_file' => 'nullable|file|max:10240',
            'enable_assessment' => 'nullable|boolean',
            'has_session_certificate' => 'nullable|boolean',
            'certificate_bg' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'text_name_y_position' => 'nullable|integer|min:0|max:100',
            'text_title_y_position' => 'nullable|integer|min:0|max:100',
        ]);

        $materialFilePath = null;
        if ($request->hasFile('material_file')) {
            $materialFilePath = $request->file('material_file')->store('courses/materials', 'public');
        }

        $certificateBgPath = null;
        if ($request->hasFile('certificate_bg')) {
            $certificateBgPath = $request->file('certificate_bg')->store('courses/certificates', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $certificateBgPath),
                $request->file('certificate_bg')->getMimeType()
            );
        }

        $sortOrder = $course->modules()->count();

        $module = Module::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'sort_order' => $sortOrder,
            'meeting_url' => $request->meeting_url,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'recording_url' => $request->recording_url,
            'material_file_path' => $materialFilePath,
            'enable_assessment' => $request->has('enable_assessment') ? filter_var($request->enable_assessment, FILTER_VALIDATE_BOOLEAN) : true,
            'has_session_certificate' => $request->has('has_session_certificate') ? filter_var($request->has_session_certificate, FILTER_VALIDATE_BOOLEAN) : false,
            'certificate_bg_path' => $certificateBgPath,
            'text_name_y_position' => $request->input('text_name_y_position', 44),
            'text_title_y_position' => $request->input('text_title_y_position', 56),
        ]);

        return response()->json([
            'message' => 'Module added successfully',
            'module' => $module->load(['lessons', 'quizzes', 'assessments.questions'])
        ]);
    }

    public function updateModule(Request $request, Module $module)
    {
        $this->validateCourseOwner($module->course);
        $request->validate([
            'title' => 'required|string|max:255',
            'meeting_url' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'recording_url' => 'nullable|string',
            'material_file' => 'nullable|file|max:10240',
            'enable_assessment' => 'nullable|boolean',
            'has_session_certificate' => 'nullable|boolean',
            'certificate_bg' => 'nullable|file|mimes:jpeg,jpg,png|max:5120',
            'text_name_y_position' => 'nullable|integer|min:0|max:100',
            'text_title_y_position' => 'nullable|integer|min:0|max:100',
        ]);

        $data = $request->only([
            'title', 'meeting_url', 'start_date', 'end_date', 'recording_url',
            'text_name_y_position', 'text_title_y_position'
        ]);

        if ($request->has('enable_assessment')) {
            $data['enable_assessment'] = filter_var($request->enable_assessment, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('has_session_certificate')) {
            $data['has_session_certificate'] = filter_var($request->has_session_certificate, FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->hasFile('material_file')) {
            $path = $request->file('material_file')->store('courses/materials', 'public');
            $data['material_file_path'] = $path;
        }

        if ($request->hasFile('certificate_bg')) {
            $bgPath = $request->file('certificate_bg')->store('courses/certificates', 'public');
            \App\Services\ImageOptimizer::optimize(
                storage_path('app/public/' . $bgPath),
                $request->file('certificate_bg')->getMimeType()
            );
            $data['certificate_bg_path'] = $bgPath;
        }

        $module->update($data);

        return response()->json([
            'message' => 'Module updated successfully',
            'module' => $module->load(['lessons', 'quizzes', 'assessments.questions'])
        ]);
    }

    public function deleteModule(Module $module)
    {
        $this->validateCourseOwner($module->course);
        $module->delete();
        return response()->json(['message' => 'Module deleted successfully']);
    }

    // --- LESSONS MANAGEMENT ---

    public function addLesson(Request $request, Module $module)
    {
        $this->validateCourseOwner($module->course);
        $slideContentObj = is_array($request->slide_content) ? $request->slide_content : json_decode($request->slide_content, true);
        if ($slideContentObj && isset($slideContentObj['type']) && $slideContentObj['type'] === 'ppt') {
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            $enableNativePpt = !isset($settings['enable_native_ppt']) || ($settings['enable_native_ppt'] !== 'false' && $settings['enable_native_ppt'] !== false && $settings['enable_native_ppt'] !== '0' && $settings['enable_native_ppt'] !== 0);
            if (!$enableNativePpt) {
                return response()->json(['message' => 'Fitur Native PPT dinonaktifkan oleh Administrator.'], 403);
            }
            $pptAccess = $settings['native_ppt_access'] ?? 'all';
            if ($pptAccess === 'admin' && !auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Akses terbatas: Hanya Admin yang diizinkan membuat Native PPT.'], 403);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|url',
            'slide_url' => [
                'nullable',
                'string',
                'url',
                function ($attribute, $value, $fail) {
                    $parsed = parse_url($value);
                    $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
                    if (strpos($host, 'docs.google.com') === false && strpos($host, 'canva.com') === false) {
                        $fail('Slide URL harus berupa tautan dari docs.google.com atau canva.com.');
                    }
                }
            ],
            'slide_content' => 'nullable',
            'duration_minutes' => 'required|integer|min:0',
        ]);

        $sortOrder = $module->lessons()->count();

        $slideUrl = null;
        if ($request->filled('slide_url')) {
            $slideUrl = $this->parseSlideUrl($request->slide_url);
        }

        $lesson = Lesson::create([
            'module_id' => $module->id,
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'slide_url' => $slideUrl,
            'slide_content' => $request->slide_content,
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
        $this->validateCourseOwner($lesson->module->course);
        $slideContentObj = is_array($request->slide_content) ? $request->slide_content : json_decode($request->slide_content, true);
        if ($slideContentObj && isset($slideContentObj['type']) && $slideContentObj['type'] === 'ppt') {
            $settings = \App\Models\Setting::pluck('value', 'key')->toArray();
            $enableNativePpt = !isset($settings['enable_native_ppt']) || ($settings['enable_native_ppt'] !== 'false' && $settings['enable_native_ppt'] !== false && $settings['enable_native_ppt'] !== '0' && $settings['enable_native_ppt'] !== 0);
            if (!$enableNativePpt) {
                return response()->json(['message' => 'Fitur Native PPT dinonaktifkan oleh Administrator.'], 403);
            }
            $pptAccess = $settings['native_ppt_access'] ?? 'all';
            if ($pptAccess === 'admin' && !auth()->user()->isAdmin()) {
                return response()->json(['message' => 'Akses terbatas: Hanya Admin yang diizinkan membuat Native PPT.'], 403);
            }
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'video_url' => 'nullable|string|url',
            'slide_url' => [
                'nullable',
                'string',
                'url',
                function ($attribute, $value, $fail) {
                    $parsed = parse_url($value);
                    $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';
                    if (strpos($host, 'docs.google.com') === false && strpos($host, 'canva.com') === false) {
                        $fail('Slide URL harus berupa tautan dari docs.google.com atau canva.com.');
                    }
                }
            ],
            'slide_content' => 'nullable',
            'duration_minutes' => 'required|integer|min:0',
        ]);

        $slideUrl = null;
        if ($request->filled('slide_url')) {
            $slideUrl = $this->parseSlideUrl($request->slide_url);
        }

        $lesson->update([
            'title' => $request->title,
            'content' => $request->content,
            'video_url' => $request->video_url,
            'slide_url' => $slideUrl,
            'slide_content' => $request->slide_content,
            'duration_minutes' => $request->duration_minutes,
        ]);

        return response()->json([
            'message' => 'Lesson updated successfully',
            'lesson' => $lesson
        ]);
    }

    private function parseSlideUrl($url)
    {
        if (empty($url)) {
            return null;
        }

        $parsed = parse_url($url);
        $host = isset($parsed['host']) ? strtolower($parsed['host']) : '';

        // Google Slides
        if (strpos($host, 'docs.google.com') !== false) {
            if (preg_match('/\/presentation\/d\/([a-zA-Z0-9_-]+)/i', $url, $matches)) {
                $presentationId = $matches[1];
                return "https://docs.google.com/presentation/d/{$presentationId}/embed?start=false&loop=false&delayms=3000";
            }
        }

        // Canva
        if (strpos($host, 'canva.com') !== false) {
            if (preg_match('/\/design\/([a-zA-Z0-9_-]+)\/([a-zA-Z0-9_-]+)/i', $url, $matches)) {
                $designId = $matches[1];
                $slug = $matches[2];
                return "https://www.canva.com/design/{$designId}/{$slug}/view?embed";
            }
        }

        return $url;
    }

    public function deleteLesson(Lesson $lesson)
    {
        $this->validateCourseOwner($lesson->module->course);
        $lesson->delete();
        return response()->json(['message' => 'Lesson deleted successfully']);
    }

    // --- QUIZZES MANAGEMENT ---

    public function addQuiz(Request $request, Module $module)
    {
        $this->validateCourseOwner($module->course);
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
        $this->validateCourseOwner($quiz->module->course);
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
        $this->validateCourseOwner($quiz->module->course);
        $quiz->delete();
        return response()->json(['message' => 'Quiz deleted successfully']);
    }

    // --- QUIZ QUESTIONS MANAGEMENT ---

    public function addQuestion(Request $request, Quiz $quiz)
    {
        $this->validateCourseOwner($quiz->module->course);
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
        $this->validateCourseOwner($question->quiz->module->course);
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
        $this->validateCourseOwner($question->quiz->module->course);
        $question->delete();
        return response()->json(['message' => 'Question deleted successfully']);
    }

    /**
     * Upload featured image or exercise file attachment for lessons
     */
    public function uploadLessonAsset(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:512000', // max 500MB
            'asset_type' => 'nullable|string|in:featured_image,attachment'
        ]);

        $file = $request->file('file');
        $assetType = $request->input('asset_type', 'attachment');

        if ($assetType === 'featured_image') {
            $path = $file->store('lessons/images', 'public');
            try {
                \App\Services\ImageOptimizer::optimize(
                    storage_path('app/public/' . $path),
                    $file->getMimeType()
                );
            } catch (\Exception $e) {}

            return response()->json([
                'success' => true,
                'url' => '/storage/' . $path,
                'name' => $file->getClientOriginalName()
            ]);
        } else {
            $path = $file->store('lessons/attachments', 'public');
            $bytes = $file->getSize();
            $sizeFormatted = $this->formatBytes($bytes);

            return response()->json([
                'success' => true,
                'url' => '/storage/' . $path,
                'name' => $file->getClientOriginalName(),
                'size' => $sizeFormatted
            ]);
        }
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
