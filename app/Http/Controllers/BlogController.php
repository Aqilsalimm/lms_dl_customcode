<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    /**
     * Display a listing of public blog posts
     */
    public function index(Request $request)
    {
        $query = Blog::where('status', 'published')->with('user');

        if ($request->has('search') && !empty($request->search)) {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%')
                  ->orWhere('category', 'like', '%' . $request->search . '%')
                  ->orWhere('tags', 'like', '%' . $request->search . '%');
            });
        }

        $blogs = $query->latest()->paginate(12);

        return Inertia::render('Blogs/Index', [
            'blogs' => $blogs,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display a single blog article details
     */
    public function show(string $slug)
    {
        $blog = Blog::where('slug', $slug)->firstOrFail();

        // If not published, only admin/author can view it
        if ($blog->status !== 'published') {
            $user = auth()->user();
            if (!$user || (!$user->isAdmin() && $blog->user_id !== $user->id)) {
                abort(403, 'Artikel ini belum disetujui untuk diterbitkan.');
            }
        }

        $relatedBlogs = Blog::where('status', 'published')
            ->where('id', '!=', $blog->id)
            ->latest()
            ->limit(4)
            ->get();

        return Inertia::render('Blogs/Show', [
            'blog' => $blog,
            'relatedBlogs' => $relatedBlogs
        ]);
    }

    /**
     * Display the Blog Settings Page for Admin and Instructors
     */
    public function settings()
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isInstructor())) {
            abort(403, 'Akses ditolak.');
        }

        // Check if instructor is allowed to access
        $allowedInstructorsJson = Setting::where('key', 'allowed_blog_instructors')->value('value');
        $allowedInstructors = $allowedInstructorsJson ? json_decode($allowedInstructorsJson, true) : [];

        if ($user->isInstructor() && !$user->isAdmin()) {
            if (!in_array($user->id, $allowedInstructors)) {
                return redirect()->route('dashboard')->with('error', 'Akses ditolak. Anda tidak memiliki izin untuk mengakses Blog Settings.');
            }
        }

        // Fetch settings or supply defaults
        $categoriesJson = Setting::where('key', 'blog_categories')->value('value');
        $categories = $categoriesJson ? json_decode($categoriesJson, true) : [
            ["id" => "1", "name" => "Coding", "parent_id" => null],
            ["id" => "2", "name" => "Web Development", "parent_id" => "1"],
            ["id" => "3", "name" => "Mobile Development", "parent_id" => "1"],
            ["id" => "4", "name" => "Data Science", "parent_id" => null]
        ];

        $tagsJson = Setting::where('key', 'blog_tags')->value('value');
        $tags = $tagsJson ? json_decode($tagsJson, true) : ["PHP", "Laravel", "Vue", "JavaScript", "HTML", "CSS"];

        $authorsJson = Setting::where('key', 'blog_authors')->value('value');
        $authors = $authorsJson ? json_decode($authorsJson, true) : ["Admin Drastha", "Dondo Ferdinanto, SE.", "Agil Salim"];

        $template = Setting::where('key', 'blog_template')->value('value') ?: '1';

        $pendingRequestJson = Setting::where('key', 'pending_blog_settings')->value('value');
        $pendingRequest = $pendingRequestJson ? json_decode($pendingRequestJson, true) : null;

        // Fetch all instructors for Admin setup
        $allInstructors = [];
        if ($user->isAdmin()) {
            $allInstructors = \App\Models\User::where('role', 'instructor')->get(['id', 'name', 'email'])->toArray();
        }

        // Fetch articles
        $blogsQuery = Blog::with('user');
        if ($user->isInstructor() && !$user->isAdmin()) {
            // Instructors manage all, or can manage their own
            $blogsQuery->where('user_id', $user->id);
        }
        $blogs = $blogsQuery->latest()->get();

        return Inertia::render('Dashboard/Admin/BlogSettings', [
            'blogSettings' => [
                'categories' => $categories,
                'tags' => $tags,
                'authors' => $authors,
                'template' => $template,
                'pending_request' => $pendingRequest,
                'allowed_instructors' => $allowedInstructors,
                'all_instructors' => $allInstructors
            ],
            'blogs' => $blogs
        ]);
    }

    /**
     * Update Blog Settings (Directly if admin, pending approval if instructor)
     */
    public function updateSettings(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isInstructor())) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        // Check if instructor is allowed to save anything
        if ($user->isInstructor() && !$user->isAdmin()) {
            $allowedInstructorsJson = Setting::where('key', 'allowed_blog_instructors')->value('value');
            $allowedInstructors = $allowedInstructorsJson ? json_decode($allowedInstructorsJson, true) : [];
            if (!in_array($user->id, $allowedInstructors)) {
                return redirect()->route('dashboard')->with('error', 'Akses ditolak.');
            }
        }

        $request->validate([
            'categories' => 'required|array',
            'tags' => 'required|array',
            'authors' => 'required|array',
            'template' => 'required|string|in:1,2,3',
            'allowed_instructors' => 'nullable|array',
        ]);

        $payload = [
            'categories' => $request->categories,
            'tags' => $request->tags,
            'authors' => $request->authors,
            'template' => $request->template,
            'requested_by' => $user->name,
            'requested_at' => now()->toDateTimeString(),
            'status' => 'pending'
        ];

        if ($user->isAdmin()) {
            // Admin updates directly
            Setting::updateOrCreate(['key' => 'blog_categories'], ['value' => json_encode($request->categories), 'group' => 'blog']);
            Setting::updateOrCreate(['key' => 'blog_tags'], ['value' => json_encode($request->tags), 'group' => 'blog']);
            Setting::updateOrCreate(['key' => 'blog_authors'], ['value' => json_encode($request->authors), 'group' => 'blog']);
            Setting::updateOrCreate(['key' => 'blog_template'], ['value' => $request->template, 'group' => 'blog']);
            Setting::updateOrCreate(['key' => 'allowed_blog_instructors'], ['value' => json_encode($request->allowed_instructors ?? []), 'group' => 'blog']);
            
            // Clear any pending requests
            Setting::where('key', 'pending_blog_settings')->delete();

            return redirect()->back()->with('success', 'Pengaturan Blog berhasil diperbarui secara langsung.');
        } else {
            // Instructor creates a pending change request
            Setting::updateOrCreate(
                ['key' => 'pending_blog_settings'],
                ['value' => json_encode($payload), 'group' => 'blog']
            );

            return redirect()->back()->with('success', 'Usulan perubahan pengaturan blog berhasil dikirim ke Super Admin untuk persetujuan.');
        }
    }

    /**
     * Approve Settings Request (Admin only)
     */
    public function approveSettingsRequest()
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya Super Admin yang dapat menyetujui pengaturan.');
        }

        $pendingRequestJson = Setting::where('key', 'pending_blog_settings')->value('value');
        if (!$pendingRequestJson) {
            return redirect()->back()->with('error', 'Tidak ada usulan perubahan yang tertunda.');
        }

        $payload = json_decode($pendingRequestJson, true);

        Setting::updateOrCreate(['key' => 'blog_categories'], ['value' => json_encode($payload['categories']), 'group' => 'blog']);
        Setting::updateOrCreate(['key' => 'blog_tags'], ['value' => json_encode($payload['tags']), 'group' => 'blog']);
        Setting::updateOrCreate(['key' => 'blog_authors'], ['value' => json_encode($payload['authors']), 'group' => 'blog']);
        Setting::updateOrCreate(['key' => 'blog_template'], ['value' => $payload['template'], 'group' => 'blog']);

        // Delete pending request
        Setting::where('key', 'pending_blog_settings')->delete();

        return redirect()->back()->with('success', 'Usulan perubahan pengaturan blog berhasil disetujui.');
    }

    /**
     * Reject Settings Request (Admin only)
     */
    public function rejectSettingsRequest()
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya Super Admin yang dapat menolak pengaturan.');
        }

        Setting::where('key', 'pending_blog_settings')->delete();

        return redirect()->back()->with('success', 'Usulan perubahan pengaturan blog berhasil ditolak.');
    }

    /**
     * Store a newly created blog article
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isInstructor())) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'excerpt' => 'nullable|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'author_name' => 'nullable|string',
        ]);

        $status = $user->isAdmin() ? 'published' : 'pending_approval';

        Blog::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'excerpt' => $request->excerpt ?: Str::limit(strip_tags($request->content), 120),
            'content' => $request->content,
            'category' => $request->category,
            'image' => $request->image ?: null,
            'tags' => $request->tags ? json_encode($request->tags) : null,
            'author_name' => $request->author_name ?: $user->name,
            'status' => $status,
        ]);

        $msg = $user->isAdmin() 
            ? 'Artikel blog baru berhasil diterbitkan secara langsung!'
            : 'Artikel blog baru telah diajukan dan menunggu persetujuan Super Admin!';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Update an existing blog article
     */
    public function update(Request $request, Blog $blog)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && $blog->user_id !== $user->id)) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'excerpt' => 'nullable|string',
            'image' => 'nullable|string',
            'tags' => 'nullable|array',
            'author_name' => 'nullable|string',
        ]);

        $status = $user->isAdmin() ? $blog->status : 'pending_approval';

        $blog->update([
            'title' => $request->title,
            'excerpt' => $request->excerpt ?: Str::limit(strip_tags($request->content), 120),
            'content' => $request->content,
            'category' => $request->category,
            'image' => $request->image ?: $blog->image,
            'tags' => $request->tags ? json_encode($request->tags) : null,
            'author_name' => $request->author_name ?: $blog->author_name,
            'status' => $status,
        ]);

        $msg = $user->isAdmin() 
            ? 'Artikel blog berhasil diperbarui!'
            : 'Perubahan artikel berhasil disimpan dan sedang menunggu persetujuan ulang Super Admin!';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Approve blog post (Admin only)
     */
    public function approvePost(Blog $blog)
    {
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya Super Admin yang dapat menyetujui artikel.');
        }

        $blog->update(['status' => 'published']);

        return redirect()->back()->with('success', 'Artikel "' . $blog->title . '" berhasil diterbitkan.');
    }

    /**
     * Remove a blog post
     */
    public function destroy(Blog $blog)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && $blog->user_id !== $user->id)) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $blog->delete();

        return redirect()->back()->with('success', 'Artikel blog berhasil dihapus.');
    }

    /**
     * Upload an image from the editor or cover section
     */
    public function uploadImage(Request $request)
    {
        $user = auth()->user();
        if (!$user || (!$user->isAdmin() && !$user->isInstructor())) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120', // max 5MB
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('blogs/uploads', 'public');
            return response()->json([
                'success' => true,
                'url' => '/storage/' . $path
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Gagal mengunggah gambar.'], 400);
    }
}
