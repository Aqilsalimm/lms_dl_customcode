<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request, string $slug)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = Auth::user();

        // Check if user has enrolled
        if (!$user->hasEnrolled($course->id)) {
            return back()->with('error', 'You must be enrolled in this course to leave a review.');
        }

        // Update or Create the review
        Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id
            ],
            [
                'rating' => $request->rating,
                'comment' => $request->comment
            ]
        );

        return back()->with('success', 'Review submitted successfully.');
    }
}
