<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Admin Dashboard Data & View
     */
    public function index(): Response
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
                'total_revenue' => 'Rp ' . number_format((float) $totalRevenue, 0, ',', '.'),
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
     * Admin action to promote/change user roles
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
     * Display LMS Settings page (Admin only)
     */
    public function settings()
    {
        $user = auth()->user();
        if (!$user->isAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access.');
        }

        $settings = Setting::pluck('value', 'key')->toArray();

        $sensitiveKeys = [
            'midtrans_server_key',
            'midtrans_client_key',
            'xendit_secret_key',
            'xendit_public_key',
            'google_client_secret',
            'google_client_id',
            'brevo_api_key'
        ];
        foreach ($sensitiveKeys as $key) {
            if (isset($settings[$key])) {
                $settings[$key] = $this->maskApiKey($settings[$key]);
            }
        }

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
            if ($this->isMaskedValue($value)) {
                continue;
            }
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_array($value) ? json_encode($value) : $value]
            );
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
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

        $testBuilderSettings = [
            'pre_passing_score' => (int) (Setting::getValue('test_builder_pre_passing_score') ?: 70),
            'post_passing_score' => (int) (Setting::getValue('test_builder_post_passing_score') ?: 70),
            'default_duration' => (int) (Setting::getValue('test_builder_default_duration') ?: 30),
            'default_max_attempts' => (int) (Setting::getValue('test_builder_default_max_attempts') ?: 3),
            'auto_enable' => filter_var(Setting::getValue('test_builder_auto_enable') ?: 'true', FILTER_VALIDATE_BOOLEAN),
            'show_explanations' => filter_var(Setting::getValue('test_builder_show_explanations') ?: 'true', FILTER_VALIDATE_BOOLEAN),
            'enforce_prerequisites' => filter_var(Setting::getValue('test_builder_enforce_prerequisites') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        ];

        $userManagementSettings = [
            'silent_delete' => filter_var(Setting::getValue('user_silent_delete') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('Dashboard/Admin/CourseBuilderSettings', [
            'categories' => $categories,
            'tags' => $tags,
            'testBuilderSettings' => $testBuilderSettings,
            'userManagementSettings' => $userManagementSettings,
        ]);
    }

    /**
     * Update User Management global settings (e.g. Silent Delete toggle).
     */
    public function updateUserManagementSettings(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'silent_delete' => 'required|boolean',
        ]);

        Setting::updateOrCreate(
            ['key' => 'user_silent_delete'],
            ['value' => $request->silent_delete ? 'true' : 'false']
        );

        return redirect()->back()->with('success', 'Pengaturan Penghapusan Pengguna berhasil diperbarui.');
    }

    /**
     * Update Test Builder global settings
     */
    public function updateTestBuilderSettings(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'pre_passing_score' => 'required|integer|min:0|max:100',
            'post_passing_score' => 'required|integer|min:0|max:100',
            'default_duration' => 'required|integer|min:1',
            'default_max_attempts' => 'required|integer|min:1',
            'auto_enable' => 'required|boolean',
            'show_explanations' => 'required|boolean',
            'enforce_prerequisites' => 'required|boolean',
        ]);

        Setting::updateOrCreate(['key' => 'test_builder_pre_passing_score'], ['value' => (string) $request->pre_passing_score]);
        Setting::updateOrCreate(['key' => 'test_builder_post_passing_score'], ['value' => (string) $request->post_passing_score]);
        Setting::updateOrCreate(['key' => 'test_builder_default_duration'], ['value' => (string) $request->default_duration]);
        Setting::updateOrCreate(['key' => 'test_builder_default_max_attempts'], ['value' => (string) $request->default_max_attempts]);
        Setting::updateOrCreate(['key' => 'test_builder_auto_enable'], ['value' => $request->auto_enable ? 'true' : 'false']);
        Setting::updateOrCreate(['key' => 'test_builder_show_explanations'], ['value' => $request->show_explanations ? 'true' : 'false']);
        Setting::updateOrCreate(['key' => 'test_builder_enforce_prerequisites'], ['value' => $request->enforce_prerequisites ? 'true' : 'false']);

        return redirect()->back()->with('success', 'Pengaturan Test Builder berhasil diperbarui.');
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
            'slug' => 'nullable|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $slug = !empty($request->slug) ? Str::slug($request->slug) : Str::slug($request->name);

        Category::create([
            'name' => $request->name,
            'slug' => $slug,
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
            'slug' => 'nullable|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:categories,id|different:id',
        ]);

        $slug = !empty($request->slug) ? Str::slug($request->slug) : Str::slug($request->name);

        $category->update([
            'name' => $request->name,
            'slug' => $slug,
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
     * Mask API keys to protect sensitive credentials from exposing on the client side
     */
    private function maskApiKey(?string $key): string
    {
        if (empty($key)) {
            return '';
        }
        if (strlen($key) <= 8) {
            return '********';
        }
        return substr($key, 0, 4) . str_repeat('*', strlen($key) - 8) . substr($key, -4);
    }

    /**
     * Check if a setting value is a masked placeholder
     */
    private function isMaskedValue($value): bool
    {
        return is_string($value) && str_contains($value, '*');
    }
}
