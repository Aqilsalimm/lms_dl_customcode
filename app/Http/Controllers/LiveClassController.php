<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LiveClassController extends Controller
{
    public function show(Request $request, Course $course)
    {
        // 1. Validasi akses menggunakan Policy
        // Jika user belum lulus Pre-test, metode ini akan melempar 403 Access Denied
        // atau kita bisa menangkapnya untuk memberikan pesan yang ramah
        
        if ($request->user()->cannot('joinLiveSession', $course)) {
            // Jika ditolak, kembalikan ke halaman silabus dengan pesan error
            return redirect()->route('courses.learn', $course->slug)
                ->with('error', 'Anda harus lulus Pre-test terlebih dahulu untuk mengakses sesi Live.');
        }

        // 2. Jika lolos, ambil data link Zoom dan render halamannya
        // Data ini aman dikirim karena user sudah divalidasi
        return Inertia::render('LiveClass/Room', [
            'course' => $course,
            'zoom_link' => $course->meeting_url ?? $course->zoom_link ?? null, // Menggunakan meeting_url dari database kita
            'materials' => $course->lessons ?? $course->materials ?? null,
        ]);
    }
}
