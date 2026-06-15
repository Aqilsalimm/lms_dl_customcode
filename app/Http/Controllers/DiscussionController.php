<?php

namespace App\Http\Controllers;

use App\Models\Discussion;
use App\Models\Lesson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DiscussionController extends Controller
{
    /**
     * Unified entry point for QnA based on role
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        if ($user->isAdmin() || $user->isInstructor()) {
            return $this->instructorInbox($request);
        }
        
        // Student view
        return (new \App\Http\Controllers\Student\QaController)->index();
    }

    /**
     * Fetch discussions for a specific material/lesson.
     */
    public function getForMaterial(Lesson $lesson)
    {
        $discussions = Discussion::with(['user', 'replies.user'])
            ->where('material_id', $lesson->id)
            ->whereNull('parent_id')
            ->latest()
            ->get();

        return response()->json($discussions);
    }

    /**
     * Store a new question or reply.
     */
    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'material_id' => 'required|exists:lessons,id',
            'parent_id' => 'nullable|exists:discussions,id',
            'body' => 'required|string',
        ]);

        $discussion = Discussion::create([
            'user_id' => auth()->id(),
            'course_id' => $request->course_id,
            'material_id' => $request->material_id,
            'parent_id' => $request->parent_id,
            'body' => $request->body,
            'is_resolved' => false,
        ]);

        return redirect()->back()->with('success', $request->parent_id ? 'Balasan dikirim' : 'Pertanyaan diajukan');
    }

    /**
     * Toggle the is_resolved status.
     */
    public function toggleResolved(Discussion $discussion)
    {
        // Only author or instructor/admin can resolve
        if (auth()->id() !== $discussion->user_id && !auth()->user()->isAdmin() && !auth()->user()->isInstructor()) {
            abort(403);
        }

        $discussion->update([
            'is_resolved' => !$discussion->is_resolved
        ]);

        return redirect()->back()->with('success', $discussion->is_resolved ? 'Ditandai selesai' : 'Dibuka kembali');
    }

    /**
     * Fetch the Centralized Inbox for instructors/admins.
     */
    public function instructorInbox(Request $request)
    {
        $user = auth()->user();
        
        $query = Discussion::with(['user', 'course', 'material', 'replies.user'])
            ->whereNull('parent_id');

        // Logic: 
        // 1. Admin/Superadmin sees ALL student comments.
        // 2. Instructor sees only comments for courses THEY created.
        if ($user->isInstructor()) {
            $query->whereHas('course', function($q) use ($user) {
                $q->where('instructor_id', $user->id);
            });
        }
        // Admin (default) has no whereHas filter, so sees everything.

        // Filters
        if ($request->filter === 'resolved') {
            $query->where('is_resolved', true);
        } elseif ($request->filter === 'unresolved') {
            $query->where('is_resolved', false);
        }

        $discussions = $query->latest()->paginate(10)->withQueryString();

        return Inertia::render('Dashboard/Admin/QnaInbox', [
            'discussions' => $discussions,
            'filters' => $request->only(['filter'])
        ]);
    }
}
