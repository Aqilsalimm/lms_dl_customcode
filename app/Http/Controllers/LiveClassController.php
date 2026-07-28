<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LiveClass;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class LiveClassController extends Controller
{
    public function show(Request $request, Course $course)
    {
        // Validasi akses menggunakan Policy / Rule
        if ($request->user()->cannot('joinLiveSession', $course)) {
            return redirect()->route('courses.learn', $course->slug)
                ->with('error', 'Anda harus lulus Pre-test terlebih dahulu untuk mengakses sesi Live.');
        }

        return Inertia::render('LiveClass/Room', [
            'course' => $course,
            'zoom_link' => $course->meeting_url ?? $course->zoom_link ?? null,
            'materials' => $course->lessons ?? $course->materials ?? null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'delivery_mode' => ['required', Rule::in(['online', 'offline'])],
            
            // Validasi Kondisional: Jika online, meeting_link wajib / valid URL; Jika offline, wajib null
            'meeting_link' => [
                Rule::requiredIf($request->delivery_mode === 'online'),
                'nullable',
                'url'
            ],
            
            // Jika offline, location_venue wajib diisi
            'location_venue' => [
                Rule::requiredIf($request->delivery_mode === 'offline'),
                'nullable',
                'string'
            ],

            // Recording & Dokumentasi bersifat opsional (dapat diisi nanti)
            'recording_url' => 'nullable|url',
            'documentation_urls' => 'nullable|array',
            'documentation_urls.*' => 'nullable|url',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
        ]);

        // Clean empty entries from documentation_urls array
        if (isset($validated['documentation_urls']) && is_array($validated['documentation_urls'])) {
            $validated['documentation_urls'] = array_values(array_filter($validated['documentation_urls'], fn($url) => !empty($url)));
        }

        // Sanitasi Data: Paksa meeting_link NULL jika offline
        if ($validated['delivery_mode'] === 'offline') {
            $validated['meeting_link'] = null;
        } else {
            $validated['location_venue'] = null;
        }

        $liveClass = LiveClass::create($validated);

        // Synchronize fallback fields to course if course_id is provided
        if (!empty($validated['course_id'])) {
            $course = Course::find($validated['course_id']);
            if ($course) {
                $course->update([
                    'delivery_mode' => $validated['delivery_mode'],
                    'meeting_url' => $validated['meeting_link'],
                    'location_venue' => $validated['location_venue'],
                    'recording_url' => $validated['recording_url'],
                    'documentation_urls' => $validated['documentation_urls'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Kelas berhasil dibuat.');
    }

    public function update(Request $request, LiveClass $liveClass)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'delivery_mode' => ['required', Rule::in(['online', 'offline'])],
            'meeting_link' => [Rule::requiredIf($request->delivery_mode === 'online'), 'nullable', 'url'],
            'location_venue' => [Rule::requiredIf($request->delivery_mode === 'offline'), 'nullable', 'string'],
            'recording_url' => 'nullable|url',
            'documentation_urls' => 'nullable|array',
            'documentation_urls.*' => 'nullable|url',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
        ]);

        if (isset($validated['documentation_urls']) && is_array($validated['documentation_urls'])) {
            $validated['documentation_urls'] = array_values(array_filter($validated['documentation_urls'], fn($url) => !empty($url)));
        }

        if ($validated['delivery_mode'] === 'offline') {
            $validated['meeting_link'] = null;
        } else {
            $validated['location_venue'] = null;
        }

        $liveClass->update($validated);

        if (!empty($validated['course_id']) || $liveClass->course_id) {
            $course = Course::find($validated['course_id'] ?? $liveClass->course_id);
            if ($course) {
                $course->update([
                    'delivery_mode' => $validated['delivery_mode'],
                    'meeting_url' => $validated['meeting_link'],
                    'location_venue' => $validated['location_venue'],
                    'recording_url' => $validated['recording_url'],
                    'documentation_urls' => $validated['documentation_urls'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(LiveClass $liveClass)
    {
        $liveClass->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }
}
