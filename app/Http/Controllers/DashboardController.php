<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Instructor\InstructorDashboardController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Models\Category;
use App\Models\Course;
use App\Models\Tag;
use App\Models\User;
use App\Services\Spreadsheet\CourseImportService;
use App\Services\Spreadsheet\DrasthaXlsxReader;
use App\Services\Spreadsheet\DrasthaXlsxWriter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AdminDashboardController $adminDashboard,
        protected InstructorDashboardController $instructorDashboard,
        protected StudentDashboardController $studentDashboard,
        protected CourseImportService $importService
    ) {}

    /**
     * Standard dashboard entry point
     */
    public function index(): Response
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return $this->adminDashboard->index();
        } elseif ($user->isInstructor()) {
            return $this->instructorDashboard->index();
        } else {
            return $this->studentDashboard->index();
        }
    }

    // --- Student Dashboard Delegates ---

    public function learningProgress(): Response
    {
        return $this->studentDashboard->learningProgress();
    }

    public function enrolledCourses(): Response
    {
        return $this->studentDashboard->enrolledCourses();
    }

    public function reviews(): Response
    {
        return $this->studentDashboard->reviews();
    }

    public function wishlist(): Response
    {
        return $this->studentDashboard->wishlist();
    }

    public function orderHistory(): Response
    {
        return $this->studentDashboard->orderHistory();
    }

    public function placeholder()
    {
        return $this->studentDashboard->placeholder();
    }

    // --- Instructor Dashboard Delegates ---

    public function liveClassSchedule()
    {
        return $this->instructorDashboard->liveClassSchedule();
    }

    public function updateLiveClassSchedule(Request $request, Course $course)
    {
        return $this->instructorDashboard->updateLiveClassSchedule($request, $course);
    }

    // --- Admin Dashboard Delegates ---

    public function changeRole(Request $request, User $user)
    {
        return $this->adminDashboard->changeRole($request, $user);
    }

    public function settings()
    {
        return $this->adminDashboard->settings();
    }

    public function updateSettings(Request $request)
    {
        return $this->adminDashboard->updateSettings($request);
    }

    public function courseBuilderSettings()
    {
        return $this->adminDashboard->courseBuilderSettings();
    }

    public function updateUserManagementSettings(Request $request)
    {
        return $this->adminDashboard->updateUserManagementSettings($request);
    }

    public function updateTestBuilderSettings(Request $request)
    {
        return $this->adminDashboard->updateTestBuilderSettings($request);
    }

    public function storeCategory(Request $request)
    {
        return $this->adminDashboard->storeCategory($request);
    }

    public function updateCategory(Request $request, Category $category)
    {
        return $this->adminDashboard->updateCategory($request, $category);
    }

    public function deleteCategory(Category $category)
    {
        return $this->adminDashboard->deleteCategory($category);
    }

    public function storeTag(Request $request)
    {
        return $this->adminDashboard->storeTag($request);
    }

    public function updateTag(Request $request, Tag $tag)
    {
        return $this->adminDashboard->updateTag($request, $tag);
    }

    public function deleteTag(Tag $tag)
    {
        return $this->adminDashboard->deleteTag($tag);
    }

    // --- Spreadsheet Course & Quiz Import Actions ---

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

    public function importCourses(Request $request): JsonResponse
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
            if (($handle = fopen($path, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
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

        $logs = $this->importService->processImportRows($rows, $courseId ? (int) $courseId : null, auth()->user());

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    public function getCoursesWithModules(): JsonResponse
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $courses = Course::with('modules')->orderBy('title')->get();

        return response()->json($courses);
    }

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

    public function importQuizzes(Request $request): JsonResponse
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
            if (($handle = fopen($path, "r")) !== false) {
                while (($data = fgetcsv($handle, 1000, ",")) !== false) {
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

        $logs = $this->importService->processQuizImportRows($rows, (string) $request->quiz_title, $request->target_module);

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }
}
