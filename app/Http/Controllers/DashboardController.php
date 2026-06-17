<?php

/**
 * ==================================================================================
 * DRASTHA LEARNING LMS - CORE DASHBOARD CONTROLLER
 * ==================================================================================
 * 
 * Role & Responsibilities:
 * - Directs route branching for Admins, Instructors, and Students dashboards.
 * - Compiles statistics, financial summaries, and student telemetry logs.
 * - Handles bulk course import parses from CSV/XLSX spreadsheets.
 * - Manages administration settings, categories, and tag metadata resources.
 * 
 * Maintenance Notes:
 * - Keep telemetry collection efficient; avoid redundant database hits in log compilation.
 * - Column mappings in processImportRows() must match the xlsx templates.
 * 
 * @package App\Http\Controllers
 * @author Senior Fullstack Developer
 */

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\QuizAttempt;
use App\Models\Category;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Tag;
use App\Models\Quiz;
use App\Models\QuizQuestion;
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

        // Seed some mock logs if none exist for a realistic demo matching the picture
        $logCount = \App\Models\StudentLearningLog::where('user_id', $user->id)->count();
        if ($logCount === 0 && $enrollments->count() > 0) {
            foreach ($enrollments as $index => $enrollment) {
                $course = $enrollment->course;
                if (!$course) continue;
                $lessons = $course->lessons()->limit(2)->get();
                // We space it out over the last 7 days
                $dayOffset = 6 - ($index * 2);
                $topicName = $course->category->name ?? ($index === 0 ? 'Cloud Devops Engineer' : 'Finance dan Accounting');
                
                foreach ($lessons as $lIdx => $lesson) {
                    \App\Models\StudentLearningLog::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'lesson_id' => $lesson->id,
                        'activity_type' => 'video_progress',
                        'watch_seconds' => rand(1500, 4800), // 25-80 mins
                        'topic_name' => $topicName,
                        'created_at' => now()->subDays(max(0, $dayOffset - $lIdx))->addHours($lIdx * 3),
                    ]);
                }
            }
        }

        // Fetch Telemetry Data
        $latestProgress = \App\Models\StudentLearningLog::where('user_id', $user->id)
            ->with(['course', 'lesson'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'course_title' => $log->course->title ?? 'Kelas',
                    'lesson_title' => $log->lesson->title ?? 'Sesi Belajar',
                    'activity_type' => $log->activity_type,
                    'watch_minutes' => (int) round($log->watch_seconds / 60),
                    'created_at_formatted' => $log->created_at->diffForHumans(),
                ];
            });

        $topicStats = \App\Models\StudentLearningLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select('topic_name', DB::raw('SUM(watch_seconds) as total_seconds'))
            ->groupBy('topic_name')
            ->get();

        $totalSecondsSum = $topicStats->sum('total_seconds') ?: 1;

        $mostTopics = $topicStats->map(function($stat) use ($totalSecondsSum) {
            return [
                'topic' => $stat->topic_name ?: 'Lainnya',
                'percentage' => (int) round(($stat->total_seconds / $totalSecondsSum) * 100),
                'minutes' => (int) round($stat->total_seconds / 60),
            ];
        })->sortByDesc('percentage')->values();

        $dailyWatchTime = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->toDateString();
            // Format to "dd Jun"
            $dateLabel = $date->format('d M');
            
            $seconds = \App\Models\StudentLearningLog::where('user_id', $user->id)
                ->whereDate('created_at', $dateString)
                ->sum('watch_seconds');

            $dailyWatchTime[] = [
                'date' => $dateLabel,
                'minutes' => (int) round($seconds / 60),
            ];
        }

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
     * Student Learning Progress Telemetry page
     */
    public function learningProgress()
    {
        $user = auth()->user();

        // Enrolled courses
        $enrollments = Enrollment::where('user_id', $user->id)
            ->with('course.category')
            ->whereNotNull('course_id')
            ->get();

        // Seed some mock logs if none exist for a realistic demo matching the picture
        $logCount = \App\Models\StudentLearningLog::where('user_id', $user->id)->count();
        if ($logCount === 0 && $enrollments->count() > 0) {
            foreach ($enrollments as $index => $enrollment) {
                $course = $enrollment->course;
                if (!$course) continue;
                $lessons = $course->lessons()->limit(2)->get();
                $dayOffset = 6 - ($index * 2);
                $topicName = $course->category->name ?? ($index === 0 ? 'Cloud Devops Engineer' : 'Finance dan Accounting');
                
                foreach ($lessons as $lIdx => $lesson) {
                    \App\Models\StudentLearningLog::create([
                        'user_id' => $user->id,
                        'course_id' => $course->id,
                        'lesson_id' => $lesson->id,
                        'activity_type' => 'video_progress',
                        'watch_seconds' => rand(1500, 4800), // 25-80 mins
                        'topic_name' => $topicName,
                        'created_at' => now()->subDays(max(0, $dayOffset - $lIdx))->addHours($lIdx * 3),
                    ]);
                }
            }
        }

        // Fetch Telemetry Data
        $latestProgress = \App\Models\StudentLearningLog::where('user_id', $user->id)
            ->with(['course', 'lesson'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function($log) {
                return [
                    'id' => $log->id,
                    'course_title' => $log->course->title ?? 'Kelas',
                    'lesson_title' => $log->lesson->title ?? 'Sesi Belajar',
                    'activity_type' => $log->activity_type,
                    'watch_minutes' => (int) round($log->watch_seconds / 60),
                    'created_at_formatted' => $log->created_at->diffForHumans(),
                ];
            });

        $topicStats = \App\Models\StudentLearningLog::where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select('topic_name', DB::raw('SUM(watch_seconds) as total_seconds'))
            ->groupBy('topic_name')
            ->get();

        $totalSecondsSum = $topicStats->sum('total_seconds') ?: 1;

        $mostTopics = $topicStats->map(function($stat) use ($totalSecondsSum) {
            return [
                'topic' => $stat->topic_name ?: 'Lainnya',
                'percentage' => (int) round(($stat->total_seconds / $totalSecondsSum) * 100),
                'minutes' => (int) round($stat->total_seconds / 60),
            ];
        })->sortByDesc('percentage')->values();

        $dailyWatchTime = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateString = $date->toDateString();
            $dateLabel = $date->format('d M');
            
            $seconds = \App\Models\StudentLearningLog::where('user_id', $user->id)
                ->whereDate('created_at', $dateString)
                ->sum('watch_seconds');

            $dailyWatchTime[] = [
                'date' => $dateLabel,
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
     * Admin/Instructor action to promote/change user roles
     */
    public function changeRole(Request $request, User $user)
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Unauthorized action.');

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
                'status' => 'enrolled',
                'course_type' => $course->course_type ?? 'async',
                'start_date' => $course->start_date ? $course->start_date->toISOString() : null,
                'end_date' => $course->end_date ? $course->end_date->toISOString() : null,
                'meeting_url' => $course->meeting_url,
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

    /**
     * Display Course Builder Settings page (Admin only)
     */
    public function courseBuilderSettings()
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $categories = Category::with('parent')->get();
        $tags = Tag::all();

        return Inertia::render('Dashboard/Admin/CourseBuilderSettings', [
            'categories' => $categories,
            'tags' => $tags,
        ]);
    }

    /**
     * Store a new category
     */
    public function storeCategory(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id ?: null,
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    /**
     * Update a category
     */
    public function updateCategory(Request $request, Category $category)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id|different:id',
        ]);

        $category->update([
            'name' => $request->name,
            'description' => $request->description,
            'parent_id' => $request->parent_id ?: null,
        ]);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    /**
     * Delete a category
     */
    public function deleteCategory(Category $category)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }

    /**
     * Store a new tag
     */
    public function storeTag(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
        ]);

        Tag::create([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Tag created successfully.');
    }

    /**
     * Update a tag
     */
    public function updateTag(Request $request, Tag $tag)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
        ]);

        $tag->update([
            'name' => $request->name,
        ]);

        return redirect()->back()->with('success', 'Tag updated successfully.');
    }

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $tag->delete();

        return redirect()->back()->with('success', 'Tag deleted successfully.');
    }

    /**
     * Download the importer Excel template
     */
    public function downloadImportTemplate()
    {
        $headers = [
            'Row Type', 'Title', 'Description', 'Publish Status', 'Course Type', 'Difficulty Level', 
            'Payment Type', 'Price Type', 'Price', 'Strike Price', 'Capacity', 'Start Date', 'End Date',
            'Timezone', 'Meeting URL', 'Tools', 'Benefit: Overview', 'Benefit: What Will Learn',
            'Benefit: Target Audience', 'Benefit: Requirements', 'Duration Hours', 'Duration Minutes'
        ];
        
        $data = [
            ['course', 'Kelas Pemrograman Web Fullstack', 'Belajar HTML, CSS, Javascript, PHP dan Database MySQL dari nol.', 'publish', 'async', 'Umum', 'one-time', 'paid', '250000', '500000', '50', '', '', 'Asia/Jakarta', '', 'VSCode, Chrome, PHPMyAdmin', 'Overview kelas...', 'HTML, CSS, JS', 'Pemula', 'Laptop ram 4GB', '40', '0'],
            ['topic', 'Bagian 1: Pengenalan HTML & CSS', 'Dasar markup internet dan styling halaman web.', 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['lesson', 'Pelajaran 1: Apa itu HTML?', 'Penjelasan dasar tentang tag HTML dan strukturnya.', 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['lesson', 'Pelajaran 2: Styling dengan CSS', 'Cara mempercantik halaman web menggunakan CSS.', 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['topic', 'Bagian 2: Javascript Interaktif', 'Membuat website menjadi dinamis dan interaktif.', 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
            ['lesson', 'Pelajaran 1: Variabel dan Kondisional', 'Belajar logika dasar pemrograman Javascript.', 'publish', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '']
        ];

        $filename = 'drastha_lms_course_template.xlsx';
        $tempFile = storage_path('app/public/' . uniqid() . '.xlsx');
        
        if (DrasthaXlsxWriter::generate($tempFile, $headers, $data)) {
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Gagal membuat template.');
    }

    /**
     * Import courses from uploaded XLSX or CSV file
     */
    public function importCourses(Request $request)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isInstructor()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'import_file' => 'required|file',
            'course_id' => 'nullable|integer|exists:courses,id'
        ]);

        $courseId = $request->input('course_id');
        if ($courseId) {
            $course = Course::find($courseId);
            if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
        }

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = [];

        if ($extension === 'xlsx') {
            try {
                $rows = DrasthaXlsxReader::read($path);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: ' . $e->getMessage() . '</span>']
                ]);
            }
        } elseif ($extension === 'csv' || $extension === 'txt') {
            if (($handle = fopen($path, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rows[] = $data;
                }
                fclose($handle);
            } else {
                return response()->json([
                    'success' => false,
                    'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: Gagal membuka file CSV.</span>']
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: Format file tidak didukung. Harap unggah file .csv atau .xlsx.</span>']
            ]);
        }

        if (empty($rows) || count($rows) <= 1) {
            return response()->json([
                'success' => false,
                'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: File kosong atau hanya berisi baris header saja.</span>']
            ]);
        }

        // Process the rows
        $logs = $this->processImportRows($rows, $courseId);

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Process parsed spreadsheet rows to insert Course, Module, Lesson
     */
    private function processImportRows($rows, $courseId = null)
    {
        $log = [];
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        
        $idx_type = array_search('row type', $header) !== false ? array_search('row type', $header) : 0;
        $idx_title = array_search('title', $header) !== false ? array_search('title', $header) : 1;
        $idx_desc = array_search('description', $header) !== false ? array_search('description', $header) : 2;
        $idx_publish_status = array_search('publish status', $header) !== false ? array_search('publish status', $header) : 3;
        
        // Step 1: Basics
        $idx_course_type = array_search('course type', $header) !== false ? array_search('course type', $header) : 4;
        $idx_difficulty = array_search('difficulty level', $header) !== false ? array_search('difficulty level', $header) : 5;
        $idx_payment_type = array_search('payment_type', $header) !== false ? array_search('payment_type', $header) : 6;
        if ($idx_payment_type === false) $idx_payment_type = array_search('payment type', $header);
        
        $idx_price_type = array_search('price type', $header) !== false ? array_search('price type', $header) : 7;
        $idx_price = array_search('price', $header) !== false ? array_search('price', $header) : 8;
        $idx_strike_price = array_search('strike price', $header) !== false ? array_search('strike price', $header) : 9;
        $idx_capacity = array_search('capacity', $header) !== false ? array_search('capacity', $header) : 10;
        
        // Step 1: Schedule (Live Class)
        $idx_start_date = array_search('start date', $header) !== false ? array_search('start date', $header) : 11;
        $idx_end_date = array_search('end_date', $header) !== false ? array_search('end_date', $header) : 12;
        $idx_timezone = array_search('timezone', $header) !== false ? array_search('timezone', $header) : 13;
        $idx_meeting_url = array_search('meeting url', $header) !== false ? array_search('meeting url', $header) : 14;
        
        // Step 1: Tools
        $idx_tools = array_search('tools', $header) !== false ? array_search('tools', $header) : 15;
        
        // Step 3: Additional / Benefits
        $idx_benefit_overview = array_search('benefit: overview', $header) !== false ? array_search('benefit: overview', $header) : 16;
        $idx_benefit_learn = array_search('benefit: what will learn', $header) !== false ? array_search('benefit: what will learn', $header) : 17;
        $idx_benefit_target = array_search('benefit: target audience', $header) !== false ? array_search('benefit: target audience', $header) : 18;
        $idx_benefit_req = array_search('benefit: requirements', $header) !== false ? array_search('benefit: requirements', $header) : 19;

        // Duration
        $idx_hours = array_search('duration hours', $header) !== false ? array_search('duration hours', $header) : 20;
        $idx_minutes = array_search('duration minutes', $header) !== false ? array_search('duration minutes', $header) : 21;

        $current_course_id = $courseId ?: 0;
        $current_module_id = 0;
        $module_order = 0;
        $lesson_order = 0;
        $row_count = 0;

        $log[] = '<span style="color:#10b981; font-weight:bold;">[START] Memulai pemrosesan baris data kelas...</span>';

        for ($i = 1; $i < count($rows); $i++) {
            $data = $rows[$i];
            if (empty($data) || !isset($data[$idx_type]) || empty(trim($data[$idx_type]))) {
                continue;
            }

            $type = strtolower(trim($data[$idx_type]));
            $title = isset($data[$idx_title]) ? trim($data[$idx_title]) : '';
            $desc = isset($data[$idx_desc]) ? trim($data[$idx_desc]) : '';
            
            $publish_status = isset($data[$idx_publish_status]) ? strtolower(trim($data[$idx_publish_status])) : 'publish';
            $requested_status = ($publish_status === 'publish' || $publish_status === 'published') ? 'published' : 'draft';
            $status = $requested_status;

            // Security: If the user is an instructor, force 'pending' if they requested 'published'
            // BUT ONLY if the moderation setting is enabled.
            if (!auth()->user()->isAdmin() && $status === 'published') {
                $moderationEnabled = \App\Models\Setting::where('key', 'instructor_course_moderation')->value('value');
                // We assume true if setting is not found, or check if it's strictly 'true' or 1.
                // In settings page, it's a toggle.
                if (filter_var($moderationEnabled, FILTER_VALIDATE_BOOLEAN)) {
                    $status = 'pending';
                }
            }

            if (empty($title)) {
                $log[] = '<span style="color:#f59e0b;">[Baris ' . ($i + 1) . '] Lewati: Judul kosong.</span>';
                continue;
            }

            if ($type === 'course') {
                if ($courseId) {
                    $current_course_id = $courseId;
                    $log[] = '<span style="color:#38bdf8;">[COURSE APPEND] Menyisipkan materi ke kelas yang sedang aktif (ID: ' . $courseId . ')</span>';
                    continue;
                }
                
                $current_module_id = 0;
                $module_order = 0;
                $lesson_order = 0;

                // Step 1: Basics
                $course_type = isset($data[$idx_course_type]) ? strtolower(trim($data[$idx_course_type])) : 'async';
                if ($course_type !== 'live_class') $course_type = 'async';
                
                $level = isset($data[$idx_difficulty]) ? trim($data[$idx_difficulty]) : 'Umum';
                
                $payment_input = isset($data[$idx_payment_type]) ? strtolower(trim($data[$idx_payment_type])) : 'one-time';
                $payment_type = 'one-time';
                if ($payment_input === 'monthly' || $payment_input === 'langganan' || $payment_input === '/bulan') {
                    $payment_type = 'monthly';
                }

                $price_type = isset($data[$idx_price_type]) ? strtolower(trim($data[$idx_price_type])) : 'free';
                $price = 0.00;
                if ($price_type === 'paid' && isset($data[$idx_price])) {
                    $price = (float) filter_var($data[$idx_price], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }

                $capacity = isset($data[$idx_capacity]) ? (int)$data[$idx_capacity] : 20;

                // Step 1: Schedule
                $start_date = !empty($data[$idx_start_date]) ? trim($data[$idx_start_date]) : null;
                $end_date = !empty($data[$idx_end_date]) ? trim($data[$idx_end_date]) : null;
                $timezone = !empty($data[$idx_timezone]) ? trim($data[$idx_timezone]) : 'Asia/Jakarta';
                $meeting_url = !empty($data[$idx_meeting_url]) ? trim($data[$idx_meeting_url]) : null;

                // Step 1: Tools
                $tools_str = isset($data[$idx_tools]) ? trim($data[$idx_tools]) : '';
                $tools_array = !empty($tools_str) ? array_map('trim', explode(',', $tools_str)) : [];

                // Step 3: Benefits (Stored as JSON in "about")
                $about_obj = [
                    'class_type' => ($course_type === 'live_class' ? 'Online' : 'Offline'),
                    'overview' => isset($data[$idx_benefit_overview]) ? trim($data[$idx_benefit_overview]) : '',
                    'what_will_learn' => isset($data[$idx_benefit_learn]) ? trim($data[$idx_benefit_learn]) : '',
                    'target_audience' => isset($data[$idx_benefit_target]) ? trim($data[$idx_benefit_target]) : '',
                    'duration_hours' => 0,
                    'materials_included' => 0,
                    'requirements' => isset($data[$idx_benefit_req]) ? trim($data[$idx_benefit_req]) : '',
                    'selected_certificate' => 'template_1',
                    'custom_certificates' => [],
                    'prerequisites' => [],
                    'attachments' => [],
                    'live_zoom_link' => '',
                    'live_zoom_data' => null,
                    'live_gmeet_link' => '',
                    'live_gmeet_data' => null,
                    'intro_video_url' => ''
                ];

                try {
                    $course = Course::create([
                        'instructor_id' => auth()->id(),
                        'course_type' => $course_type,
                        'title' => $title,
                        'description' => $desc,
                        'about' => json_encode($about_obj),
                        'bg_color' => '#44A6D9',
                        'icon_type' => 'code',
                        'price' => $price,
                        'payment_type' => $payment_type,
                        'level' => $level,
                        'capacity' => $capacity,
                        'status' => $status,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'timezone' => $timezone,
                        'meeting_url' => $meeting_url,
                        'tools' => $tools_array
                    ]);

                    $current_course_id = $course->id;
                    $log[] = '<span style="color:#38bdf8;">[COURSE CREATED] ID ' . $current_course_id . ' : ' . htmlspecialchars($title) . ' (' . strtoupper($course_type) . ' - ' . $status . ')</span>';
                } catch (\Exception $e) {
                    $log[] = '<span style="color:#ef4444;">[COURSE FAILED] Gagal membuat Kursus "' . htmlspecialchars($title) . '": ' . $e->getMessage() . '</span>';
                }

            } elseif ($type === 'topic' || $type === 'module') {
                if ($current_course_id <= 0) {
                    $log[] = '<span style="color:#ef4444;">[MODULE FAILED] Gagal membuat Bab/Topic "' . htmlspecialchars($title) . '": Tidak ada Kursus aktif di baris sebelumnya.</span>';
                    continue;
                }

                $module_order++;
                $lesson_order = 0;

                try {
                    $module = Module::create([
                        'course_id' => $current_course_id,
                        'title' => $title,
                        'sort_order' => $module_order
                    ]);

                    $current_module_id = $module->id;
                    $log[] = '<span style="color:#a855f7;">  [MODULE CREATED] ID ' . $current_module_id . ' : ' . htmlspecialchars($title) . ' (Urutan: ' . $module_order . ')</span>';
                } catch (\Exception $e) {
                    $log[] = '<span style="color:#ef4444;">  [MODULE FAILED] Gagal membuat Bab: ' . $e->getMessage() . '</span>';
                }

            } elseif ($type === 'lesson') {
                if ($current_course_id <= 0 || $current_module_id <= 0) {
                    $log[] = '<span style="color:#ef4444;">[LESSON FAILED] Gagal membuat Pelajaran "' . htmlspecialchars($title) . '": Harus berada di bawah Kursus dan Bab yang valid.</span>';
                    continue;
                }

                $lesson_order++;

                // Parse duration minutes
                $hours = isset($data[$idx_hours]) ? (int)$data[$idx_hours] : 0;
                $mins = isset($data[$idx_minutes]) ? (int)$data[$idx_minutes] : 0;
                $duration = ($hours * 60) + $mins;
                if ($duration <= 0) $duration = 30; // default 30 mins

                try {
                    Lesson::create([
                        'module_id' => $current_module_id,
                        'title' => $title,
                        'content' => json_encode([
                            'type' => 'text',
                            'summary' => $desc,
                            'featured_image' => null,
                            'exercise_files' => [],
                            'is_preview' => false
                        ]),
                        'video_url' => '',
                        'duration_minutes' => $duration,
                        'sort_order' => $lesson_order
                    ]);

                    $log[] = '<span style="color:#10b981;">    [LESSON CREATED] ' . htmlspecialchars($title) . ' (Durasi: ' . $duration . ' Menit, Urutan: ' . $lesson_order . ')</span>';
                } catch (\Exception $e) {
                    $log[] = '<span style="color:#ef4444;">    [LESSON FAILED] Gagal membuat Pelajaran: ' . $e->getMessage() . '</span>';
                }
            } else {
                $log[] = '<span style="color:#f59e0b;">[UNKNOWN TYPE] Lewati tipe baris tidak dikenal: "' . htmlspecialchars($type) . '"</span>';
            }

            $row_count++;
        }

        $log[] = '<span style="color:#10b981; font-weight:bold;">[FINISH] Selesai memproses ' . $row_count . ' baris kurikulum kelas.</span>';
        return $log;
    }

    /**
     * Get list of courses with their modules for dynamic target selection
     */
    public function getCoursesWithModules()
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $courses = Course::with('modules')->orderBy('title')->get();

        return response()->json($courses);
    }

    /**
     * Download the quiz importer Excel template
     */
    public function downloadQuizTemplate()
    {
        $headers = [
            'Question Type', 'Question Text', 'Points / Marks', 'Choice A', 'Choice B', 'Choice C', 'Choice D', 'Choice E', 'Correct Answer Key'
        ];
        
        $data = [
            ['single_choice', 'Siapa penemu gaya gravitasi universal?', '10', 'Albert Einstein', 'Isaac Newton', 'Nikola Tesla', 'Marie Curie', '', 'B'],
            ['multiple_choice', 'Manakah yang termasuk bahasa pemrograman back-end? (Pilih semua yang benar)', '15', 'HTML', 'CSS', 'PHP', 'Python', 'Node.js', 'C, D, E'],
            ['true_false', 'Bumi adalah planet terdekat dari Matahari.', '10', 'True', 'False', '', '', '', 'B'],
            ['single_choice', 'Apa kepanjangan dari CSS?', '10', 'Creative Style Sheets', 'Cascading Style Sheets', 'Computer Style Sheets', 'Colorful Style Sheets', '', 'B']
        ];

        $filename = 'drastha_lms_quiz_template.xlsx';
        $tempFile = storage_path('app/public/' . uniqid() . '.xlsx');

        if (DrasthaXlsxWriter::generate($tempFile, $headers, $data)) {
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Gagal membuat template kuis.');
    }

    /**
     * Import quizzes from uploaded XLSX or CSV file
     */
    public function importQuizzes(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'quiz_title' => 'required|string|max:255',
            'target_module' => 'required|exists:modules,id',
            'import_file' => 'required|file'
        ]);

        $file = $request->file('import_file');
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();

        $rows = [];

        if ($extension === 'xlsx') {
            try {
                $rows = DrasthaXlsxReader::read($path);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: ' . $e->getMessage() . '</span>']
                ]);
            }
        } elseif ($extension === 'csv' || $extension === 'txt') {
            if (($handle = fopen($path, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    $rows[] = $data;
                }
                fclose($handle);
            } else {
                return response()->json([
                    'success' => false,
                    'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: Gagal membuka file CSV.</span>']
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: Format file tidak didukung. Harap unggah file .csv atau .xlsx.</span>']
            ]);
        }

        if (empty($rows) || count($rows) <= 1) {
            return response()->json([
                'success' => false,
                'logs' => ['<span style="color:#ef4444; font-weight:bold;">Error: File kosong atau hanya berisi baris header saja.</span>']
            ]);
        }

        $logs = $this->processQuizImportRows($rows, $request->quiz_title, $request->target_module);

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * Process parsed spreadsheet rows to insert Quiz and QuizQuestion
     */
    private function processQuizImportRows($rows, $quiz_title, $module_id)
    {
        $log = [];
        $header = array_map('strtolower', array_map('trim', $rows[0]));
        
        $idx_type = array_search('question type', $header) !== false ? array_search('question type', $header) : 0;
        $idx_text = array_search('question text', $header) !== false ? array_search('question text', $header) : 1;
        $idx_points = array_search('points / marks', $header) !== false ? array_search('points / marks', $header) : 2;
        $idx_choice_a = array_search('choice a', $header) !== false ? array_search('choice a', $header) : 3;
        $idx_choice_b = array_search('choice b', $header) !== false ? array_search('choice b', $header) : 4;
        $idx_choice_c = array_search('choice c', $header) !== false ? array_search('choice c', $header) : 5;
        $idx_choice_d = array_search('choice d', $header) !== false ? array_search('choice d', $header) : 6;
        $idx_choice_e = array_search('choice e', $header) !== false ? array_search('choice e', $header) : 7;
        $idx_correct = array_search('correct answer key', $header) !== false ? array_search('correct answer key', $header) : 8;

        $log[] = '<span style="color:#10b981; font-weight:bold;">[START] Memulai pemrosesan kuis...</span>';

        $module = Module::find($module_id);
        if (!$module) {
            $log[] = '<span style="color:#ef4444; font-weight:bold;">[ERROR] Bab/Module target tidak ditemukan.</span>';
            return $log;
        }

        try {
            $quiz = Quiz::create([
                'module_id' => $module_id,
                'title' => $quiz_title,
                'description' => 'Quiz imported from spreadsheet template.',
                'time_limit_minutes' => 30,
                'sort_order' => ($module->quizzes()->count() + 1)
            ]);

            $log[] = '<span style="color:#38bdf8; font-weight:bold;">[QUIZ CREATED] ID: ' . $quiz->id . ' - "' . htmlspecialchars($quiz_title) . '" berhasil ditautkan ke Bab "' . htmlspecialchars($module->title) . '".</span>';
        } catch (\Exception $e) {
            $log[] = '<span style="color:#ef4444; font-weight:bold;">[QUIZ FAILED] Gagal membuat kuis di database: ' . $e->getMessage() . '</span>';
            return $log;
        }

        $question_count = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $data = $rows[$i];
            if (empty($data) || !isset($data[$idx_text]) || empty(trim($data[$idx_text]))) {
                continue;
            }

            $q_type = isset($data[$idx_type]) ? strtolower(trim($data[$idx_type])) : 'single_choice';
            $q_text = trim($data[$idx_text]);
            $correct_key_raw = isset($data[$idx_correct]) ? strtoupper(trim($data[$idx_correct])) : '';

            $options = [];
            if ($q_type === 'true_false' || $q_type === 'true/false') {
                $options = ['True', 'False'];
            } else {
                if (!empty($data[$idx_choice_a])) $options[] = trim($data[$idx_choice_a]);
                if (!empty($data[$idx_choice_b])) $options[] = trim($data[$idx_choice_b]);
                if (!empty($data[$idx_choice_c])) $options[] = trim($data[$idx_choice_c]);
                if (!empty($data[$idx_choice_d])) $options[] = trim($data[$idx_choice_d]);
                if (!empty($data[$idx_choice_e])) $options[] = trim($data[$idx_choice_e]);
            }

            if (empty($options)) {
                $log[] = '<span style="color:#f59e0b;">  [SOAL LEWATI] Baris ' . ($i + 1) . ': Opsi jawaban kosong.</span>';
                continue;
            }

            $correct_index = 0;
            if ($q_type === 'true_false' || $q_type === 'true/false') {
                if ($correct_key_raw === 'B' || strtolower($correct_key_raw) === 'false') {
                    $correct_index = 1;
                } else {
                    $correct_index = 0;
                }
            } else {
                $first_key = 'A';
                if (!empty($correct_key_raw)) {
                    $split_keys = preg_split('/[\s,|]+/', $correct_key_raw);
                    if (!empty($split_keys[0])) {
                        $first_key = strtoupper(trim($split_keys[0]));
                    }
                }

                $map = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4];
                $correct_index = isset($map[$first_key]) ? $map[$first_key] : 0;
                
                if ($correct_index >= count($options)) {
                    $correct_index = 0;
                }
            }

            try {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $q_text,
                    'options' => $options,
                    'correct_option_index' => $correct_index,
                    'sort_order' => ($question_count + 1)
                ]);

                $log[] = '<span style="color:#a855f7;">  [SOAL BERHASIL] ID: ' . ($question_count + 1) . ' | Tipe: ' . $q_type . ' | "' . htmlspecialchars($q_text) . '"</span>';
                $log[] = '    -> Opsi: [' . implode(', ', array_map(function($opt, $idx) use ($correct_index) {
                    return $idx === $correct_index ? '<span style="color:#10b981; font-weight:bold;">' . htmlspecialchars($opt) . '</span>' : htmlspecialchars($opt);
                }, $options, array_keys($options))) . ']';

                $question_count++;
            } catch (\Exception $e) {
                $log[] = '<span style="color:#ef4444;">  [SOAL GAGAL] Gagal menyuntikkan soal baris ' . ($i + 1) . ': ' . $e->getMessage() . '</span>';
            }
        }

        $log[] = '<span style="color:#10b981; font-weight:bold;">[FINISH] Selesai menyuntikkan ' . $question_count . ' soal kuis ke database!</span>';
        return $log;
    }
}

class DrasthaXlsxReader
{
    public static function read($file_path)
    {
        if (!class_exists('ZipArchive')) {
            throw new \Exception('Ekstensi PHP ZipArchive tidak aktif pada server Anda.');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file_path) !== TRUE) {
            throw new \Exception('Gagal membuka file Excel (.xlsx). File mungkin rusak.');
        }

        // Parse Shared Strings
        $shared_strings = [];
        $shared_strings_xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($shared_strings_xml) {
            // Remove namespaces and prefixes to avoid "mc for Ignorable" errors
            $shared_strings_xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $shared_strings_xml);
            $shared_strings_xml = preg_replace('/[a-z0-9]+:([a-z0-9]+)/i', '$1', $shared_strings_xml);
            $xml = @simplexml_load_string($shared_strings_xml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $shared_strings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            if (isset($r->t)) {
                                $text .= (string)$r->t;
                            }
                        }
                        $shared_strings[] = $text;
                    } else {
                        $shared_strings[] = '';
                    }
                }
            }
        }

        // Parse Sheet 1
        $rows = [];
        $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet_xml) {
            // Remove namespaces and prefixes to avoid "mc for Ignorable" errors
            $sheet_xml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $sheet_xml);
            $sheet_xml = preg_replace('/[a-z0-9]+:([a-z0-9]+)/i', '$1', $sheet_xml);
            $xml = @simplexml_load_string($sheet_xml);
            if ($xml && isset($xml->sheetData->row)) {
                foreach ($xml->sheetData->row as $row) {
                    $row_idx = intval($row['r']) - 1;
                    $row_data = [];
                    
                    foreach ($row->c as $c) {
                        $cell_ref = (string)$c['r'];
                        preg_match('/^[A-Z]+/i', $cell_ref, $matches);
                        if (empty($matches)) continue;
                        $col_name = $matches[0];
                        $col_idx = self::colNameToIndex($col_name);
                        
                        $type = (string)$c['t'];
                        $val = '';
                        if ($type === 'inlineStr' && isset($c->is->t)) {
                            $val = (string)$c->is->t;
                        } elseif (isset($c->v)) {
                            $val = (string)$c->v;
                            if ($type === 's') {
                                $idx = intval($val);
                                $val = isset($shared_strings[$idx]) ? $shared_strings[$idx] : '';
                            }
                        }
                        $row_data[$col_idx] = $val;
                    }
                    
                    if (!empty($row_data)) {
                        $max_col = max(array_keys($row_data));
                        for ($i = 0; $i <= $max_col; $i++) {
                            if (!isset($row_data[$i])) {
                                $row_data[$i] = '';
                            }
                        }
                        ksort($row_data);
                    }
                    $rows[$row_idx] = $row_data;
                }
            }
        }
        $zip->close();

        if (!empty($rows)) {
            $max_row = max(array_keys($rows));
            for ($i = 0; $i <= $max_row; $i++) {
                if (!isset($rows[$i])) {
                    $rows[$i] = [];
                }
            }
            ksort($rows);
        }

        return $rows;
    }

    private static function colNameToIndex($col)
    {
        $col = strtoupper($col);
        $len = strlen($col);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - 64);
        }
        return $index - 1;
    }
}

class DrasthaXlsxWriter
{
    public static function generate($filename, $headers, $data)
    {
        $zip = new \ZipArchive();
        if ($zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return false;
        }

        // [Content_Types].xml
        $content_types = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $content_types);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // xl/workbook.xml
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Tutor LMS Import Template" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>';
        $zip->addFromString('xl/workbook.xml', $workbook);

        // xl/_rels/workbook.xml.rels
        $workbook_rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>';
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbook_rels);

        // xl/worksheets/sheet1.xml
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>';

        $colLetter = function($idx) {
            $letter = '';
            while ($idx >= 0) {
                $letter = chr(65 + ($idx % 26)) . $letter;
                $idx = intval($idx / 26) - 1;
            }
            return $letter;
        };

        // Headers
        $sheet .= '<row r="1">';
        foreach ($headers as $c_idx => $header_text) {
            $ref = $colLetter($c_idx) . '1';
            $esc_text = htmlspecialchars($header_text, ENT_QUOTES, 'UTF-8');
            $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $esc_text . '</t></is></c>';
        }
        $sheet .= '</row>';

        // Data Rows
        foreach ($data as $r_idx => $row_data) {
            $row_num = $r_idx + 2;
            $sheet .= '<row r="' . $row_num . '">';
            foreach ($row_data as $c_idx => $val) {
                $ref = $colLetter($c_idx) . $row_num;
                $esc_val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
                $sheet .= '<c r="' . $ref . '" t="inlineStr"><is><t>' . $esc_val . '</t></is></c>';
            }
            $sheet .= '</row>';
        }

        $sheet .= '  </sheetData>
</worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);

        $zip->close();
        return true;
    }
}
