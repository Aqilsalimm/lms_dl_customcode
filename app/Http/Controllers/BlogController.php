<?php

namespace App\Http\Controllers;

use App\Models\Blog;
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
            $query->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('content', 'like', '%' . $request->search . '%');
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
        $blog = Blog::where('slug', $slug)
            ->where('status', 'published')
            ->with('user')
            ->firstOrFail();

        return Inertia::render('Blogs/Show', [
            'blog' => $blog
        ]);
    }

    /**
     * Store a newly created blog article in database (Admin only)
     */
    public function store(Request $request)
    {
        // 1. Authorization check
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya administrator / superadmin yang memiliki akses untuk membuat artikel blog.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'excerpt' => 'nullable|string',
            'image' => 'nullable|string',
        ]);

        Blog::create([
            'user_id' => $user->id,
            'title' => $request->title,
            'slug' => Str::slug($request->title) . '-' . uniqid(),
            'excerpt' => $request->excerpt ?: Str::limit(strip_tags($request->content), 120),
            'content' => $request->content,
            'category' => $request->category,
            'image' => $request->image ?: null,
            'status' => 'published',
        ]);

        return redirect()->back()->with('success', 'Artikel blog baru berhasil diterbitkan!');
    }

    /**
     * Remove a blog post (Admin only)
     */
    public function destroy(Blog $blog)
    {
        // Authorization check
        $user = auth()->user();
        if (!$user || !$user->isAdmin()) {
            return redirect()->back()->with('error', 'Hanya administrator yang memiliki akses untuk menghapus artikel.');
        }

        $blog->delete();

        return redirect()->back()->with('success', 'Artikel blog berhasil dihapus.');
    }
}
