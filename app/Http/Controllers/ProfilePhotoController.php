<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ProfilePhotoController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:2048'], // Max 2MB
        ]);

        $user = $request->user();

        // Delete old photo if exists
        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }

        $path = $request->file('photo')->store('images/profiles', 'public');
        
        $user->photo = $path;
        $user->save();

        return Redirect::back()->with('success', 'Profile photo updated successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
            
            $user->photo = null;
            $user->save();
        }

        return Redirect::back()->with('success', 'Profile photo removed successfully.');
    }
}
