<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LiveClass;
use App\Models\ClassEnrollment;
use App\Models\Enrollment;
use App\Http\Requests\SelectAttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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

        // Fetch live classes and their attendance preferences for the user
        $liveClasses = LiveClass::where('course_id', $course->id)
            ->where('is_published', true)
            ->with(['classEnrollments' => function($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            }])
            ->get()
            ->map(function($lc) {
                $preference = $lc->classEnrollments->first();
                return [
                    'id' => $lc->id,
                    'title' => $lc->title,
                    'mode' => $lc->mode,
                    'meeting_link' => $lc->meeting_link,
                    'location_venue' => $lc->location_venue,
                    'venue_name' => $lc->venue_name,
                    'venue_address' => $lc->venue_address,
                    'gmaps_url' => $lc->gmaps_url,
                    'gmaps_embed_url' => $lc->gmaps_embed_url,
                    'offline_capacity' => $lc->offline_capacity,
                    'start_time' => $lc->start_time,
                    'end_time' => $lc->end_time,
                    'attendance_preference' => $preference ? $preference->attendance_type : null,
                    'checkin_qr_code' => $preference ? $preference->checkin_qr_code : null,
                ];
            });

        // Strict Payload Trimming (No Eloquent Model Dumping for Security)
        return Inertia::render('LiveClass/Room', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'delivery_mode' => $course->delivery_mode,
                'start_date' => $course->start_date,
                'timezone' => $course->timezone,
                'location_venue' => $course->location_venue,
                'instructor' => $course->instructor ? [
                    'id' => $course->instructor->id,
                    'name' => $course->instructor->name,
                    'email' => $course->instructor->email,
                ] : null,
            ],
            'zoom_link' => $course->meeting_url ?? $course->zoom_link ?? null,
            'materials' => $course->lessons ?? $course->materials ?? null,
            'live_classes' => $liveClasses,
        ]);
    }

    public function selectAttendance(SelectAttendanceRequest $request, LiveClass $liveClass)
    {
        $user = $request->user();

        // Anti-IDOR: Confirm active enrollment in the corresponding course
        $isEnrolled = Enrollment::where('user_id', $user->id)
            ->where('course_id', $liveClass->course_id)
            ->where('status', 'active')
            ->exists();

        if (!$isEnrolled) {
            abort(403, 'Akses ditolak: Anda harus terdaftar di kursus ini untuk memilih kehadiran.');
        }

        // Check H-1 cutoff before changes
        if ($liveClass->start_time && now()->diffInHours($liveClass->start_time, false) < 24) {
            return redirect()->back()->with('error', 'Perubahan tipe kehadiran hanya dapat dilakukan paling lambat H-1 sebelum acara dimulai.');
        }

        try {
            DB::transaction(function () use ($liveClass, $user, $request) {
                // Lock record for update to prevent capacity race conditions
                $lockedClass = LiveClass::where('id', $liveClass->id)->lockForUpdate()->firstOrFail();
                $attendanceType = $request->input('attendance_type');

                if ($attendanceType === 'onsite') {
                    if ($lockedClass->mode === 'online') {
                        throw new \Exception('Kelas ini diadakan secara online saja.');
                    }

                    $onsiteCount = ClassEnrollment::where('live_class_id', $lockedClass->id)
                        ->where('attendance_type', 'onsite')
                        ->count();

                    $alreadyOnsite = ClassEnrollment::where('live_class_id', $lockedClass->id)
                        ->where('user_id', $user->id)
                        ->where('attendance_type', 'onsite')
                        ->exists();

                    if (!$alreadyOnsite && $lockedClass->offline_capacity !== null && $onsiteCount >= $lockedClass->offline_capacity) {
                        throw new \Exception('Kapasitas onsite kelas ini sudah penuh.');
                    }
                }

                ClassEnrollment::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'live_class_id' => $lockedClass->id,
                    ],
                    [
                        'attendance_type' => $attendanceType,
                        'checkin_qr_code' => $attendanceType === 'onsite'
                            ? 'onsite_' . Str::random(32)
                            : null
                    ]
                );
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Pilihan kehadiran Anda berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        if (\Illuminate\Support\Facades\Gate::denies('create', LiveClass::class)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk membuat sesi kelas.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'delivery_mode' => ['required', Rule::in(['online', 'offline'])],
            'mode' => ['nullable', Rule::in(['online', 'offline', 'hybrid'])],
            
            'meeting_link' => [
                Rule::requiredIf(in_array($request->mode ?? $request->delivery_mode, ['online', 'hybrid'])),
                'nullable',
                'url'
            ],
            
            'location_venue' => [
                Rule::requiredIf(in_array($request->mode ?? $request->delivery_mode, ['offline', 'hybrid'])),
                'nullable',
                'string'
            ],

            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'gmaps_url' => 'nullable|url',
            'gmaps_embed_url' => 'nullable|url',
            'offline_capacity' => 'nullable|integer|min:0',

            'recording_url' => 'nullable|url',
            'documentation_urls' => 'nullable|array',
            'documentation_urls.*' => 'nullable|url',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ]);

        if (!empty($validated['course_id'])) {
            $course = Course::find($validated['course_id']);
            if ($course && \Illuminate\Support\Facades\Gate::denies('update', $course)) {
                abort(403, 'Akses ditolak: Anda bukan instruktur kelas ini.');
            }
        }

        if (empty($validated['mode']) && !empty($validated['delivery_mode'])) {
            $validated['mode'] = $validated['delivery_mode'];
        }
        if (!empty($validated['mode']) && empty($validated['delivery_mode'])) {
            $validated['delivery_mode'] = in_array($validated['mode'], ['offline', 'hybrid']) ? 'offline' : 'online';
        }

        if (isset($validated['documentation_urls']) && is_array($validated['documentation_urls'])) {
            $validated['documentation_urls'] = array_values(array_filter($validated['documentation_urls'], fn($url) => !empty($url)));
        }

        if ($validated['mode'] === 'offline') {
            $validated['meeting_link'] = null;
        }
        if ($validated['mode'] === 'online') {
            $validated['location_venue'] = null;
            $validated['venue_name'] = null;
            $validated['venue_address'] = null;
            $validated['gmaps_url'] = null;
            $validated['gmaps_embed_url'] = null;
            $validated['offline_capacity'] = null;
        }

        $liveClass = LiveClass::create($validated);

        if (!empty($validated['course_id'])) {
            $course = Course::find($validated['course_id']);
            if ($course) {
                $course->update([
                    'delivery_mode' => $validated['delivery_mode'],
                    'meeting_url' => $validated['meeting_link'],
                    'location_venue' => $validated['location_venue'] ?? $validated['venue_address'] ?? null,
                    'recording_url' => $validated['recording_url'],
                    'documentation_urls' => $validated['documentation_urls'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Kelas berhasil dibuat.');
    }

    public function update(Request $request, LiveClass $liveClass)
    {
        $user = $request->user();
        $course = $liveClass->course;
        if (\Illuminate\Support\Facades\Gate::denies('update', $liveClass)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk mengubah sesi kelas ini.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'course_id' => 'nullable|exists:courses,id',
            'delivery_mode' => ['required', Rule::in(['online', 'offline'])],
            'mode' => ['nullable', Rule::in(['online', 'offline', 'hybrid'])],
            'meeting_link' => [Rule::requiredIf(in_array($request->mode ?? $request->delivery_mode, ['online', 'hybrid'])), 'nullable', 'url'],
            'location_venue' => [Rule::requiredIf(in_array($request->mode ?? $request->delivery_mode, ['offline', 'hybrid'])), 'nullable', 'string'],
            
            'venue_name' => 'nullable|string|max:255',
            'venue_address' => 'nullable|string',
            'gmaps_url' => 'nullable|url',
            'gmaps_embed_url' => 'nullable|url',
            'offline_capacity' => 'nullable|integer|min:0',

            'recording_url' => 'nullable|url',
            'documentation_urls' => 'nullable|array',
            'documentation_urls.*' => 'nullable|url',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date',
            'is_published' => 'nullable|boolean',
        ]);

        if (empty($validated['mode']) && !empty($validated['delivery_mode'])) {
            $validated['mode'] = $validated['delivery_mode'];
        }
        if (!empty($validated['mode']) && empty($validated['delivery_mode'])) {
            $validated['delivery_mode'] = in_array($validated['mode'], ['offline', 'hybrid']) ? 'offline' : 'online';
        }

        if (isset($validated['documentation_urls']) && is_array($validated['documentation_urls'])) {
            $validated['documentation_urls'] = array_values(array_filter($validated['documentation_urls'], fn($url) => !empty($url)));
        }

        if ($validated['mode'] === 'offline') {
            $validated['meeting_link'] = null;
        }
        if ($validated['mode'] === 'online') {
            $validated['location_venue'] = null;
            $validated['venue_name'] = null;
            $validated['venue_address'] = null;
            $validated['gmaps_url'] = null;
            $validated['gmaps_embed_url'] = null;
            $validated['offline_capacity'] = null;
        }

        $liveClass->update($validated);

        if (!empty($validated['course_id']) || $liveClass->course_id) {
            $course = Course::find($validated['course_id'] ?? $liveClass->course_id);
            if ($course) {
                $course->update([
                    'delivery_mode' => $validated['delivery_mode'],
                    'meeting_url' => $validated['meeting_link'],
                    'location_venue' => $validated['location_venue'] ?? $validated['venue_address'] ?? null,
                    'recording_url' => $validated['recording_url'],
                    'documentation_urls' => $validated['documentation_urls'] ?? null,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Kelas berhasil diperbarui.');
    }

    public function destroy(Request $request, LiveClass $liveClass)
    {
        $user = $request->user();
        $course = $liveClass->course;
        if (\Illuminate\Support\Facades\Gate::denies('delete', $liveClass)) {
            abort(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk menghapus sesi kelas ini.');
        }

        $liveClass->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus.');
    }
}
