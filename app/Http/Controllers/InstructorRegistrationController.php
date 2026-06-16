<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\InstructorProfile;
use Illuminate\Support\Facades\Storage;

class InstructorRegistrationController extends Controller
{
    public function create()
    {
        // Ensure user is an instructor and status is pending
        $user = auth()->user();
        if ($user->role !== 'instructor' || $user->status !== 'pending') {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Auth/ProfileSetup');
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Ensure user is an instructor and status is pending
        if ($user->role !== 'instructor' || $user->status !== 'pending') {
            return redirect()->route('dashboard');
        }

        $request->validate([
            'expertise_area' => 'required|string|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'resume_file' => 'required|file|mimes:pdf,doc,docx|max:2048',
            'bio_summary' => 'required|string|max:1000',
        ]);

        $resumePath = null;
        if ($request->hasFile('resume_file')) {
            $resumePath = $request->file('resume_file')->store('resumes', 'public');
        }

        InstructorProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'expertise_area' => $request->expertise_area,
                'portfolio_url' => $request->portfolio_url,
                'resume_file' => $resumePath ? '/storage/' . $resumePath : null,
                'bio_summary' => $request->bio_summary,
            ]
        );

        // Don't change status to active yet, admin needs to approve
        
        return redirect()->route('dashboard')->with('success', 'Profil berhasil disimpan. Menunggu persetujuan Admin.');
    }
}
