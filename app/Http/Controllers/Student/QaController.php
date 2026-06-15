<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Discussion;
use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QaController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Get courses where student has participated in discussions
        $discussions = Discussion::with(['course', 'material', 'replies.user'])
            ->where('user_id', $user->id)
            ->whereNull('parent_id')
            ->latest()
            ->get()
            ->groupBy('course_id');

        $coursesWithDiscussions = $discussions->map(function ($items, $courseId) {
            $course = $items->first()->course;
            return [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'thumbnail' => $course->thumbnail,
                'instructor_name' => $course->instructor->name ?? 'Admin',
                'discussions' => $items->map(function ($discussion) {
                    return [
                        'id' => $discussion->id,
                        'body' => $discussion->body,
                        'is_resolved' => $discussion->is_resolved,
                        'created_at' => $discussion->created_at->diffForHumans(),
                        'material_title' => $discussion->material->title ?? 'Materi',
                        'replies_count' => $discussion->replies->count(),
                        'latest_reply' => $discussion->replies->last() ? [
                            'body' => $discussion->replies->last()->body,
                            'user_name' => $discussion->replies->last()->user->name,
                            'created_at' => $discussion->replies->last()->created_at->diffForHumans(),
                        ] : null,
                    ];
                }),
            ];
        })->values();

        return Inertia::render('Dashboard/Student/Qa', [
            'courses' => $coursesWithDiscussions
        ]);
    }
}
