<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Wishlist;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    public function toggle(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id'
        ]);

        $user = Auth::user();
        
        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('course_id', $request->course_id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return back()->with('success', 'Course removed from wishlist.');
        }

        Wishlist::create([
            'user_id' => $user->id,
            'course_id' => $request->course_id
        ]);

        return back()->with('success', 'Course added to wishlist.');
    }
}
