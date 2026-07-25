<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\UserCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CertificateController extends Controller
{
    /**
     * Get list of certificates and unlock status for active student
     */
    public function index(Request $request, Course $course)
    {
        $user = auth()->user();

        // Fetch course certificates with modules
        $certificates = Certificate::where('course_id', $course->id)
            ->where('is_active', true)
            ->get();

        // If no custom session certificates exist yet, auto-create default Session Certificates for each module
        if ($certificates->isEmpty() && $course->modules()->count() > 0) {
            foreach ($course->modules as $index => $module) {
                Certificate::create([
                    'course_id' => $course->id,
                    'title' => "Sertifikat Sesi " . ($index + 1) . ": " . $module->title,
                    'type' => 'session',
                    'module_ids' => [$module->id],
                    'description' => "Diberikan atas penyelesaian penuh materi dan kuis Sesi " . ($index + 1) . " (" . $module->title . ").",
                    'is_active' => true,
                ]);
            }

            // Also create Course Completion Certificate if none exists
            Certificate::create([
                'course_id' => $course->id,
                'title' => "Sertifikat Completion: " . $course->title,
                'type' => 'course_completion',
                'module_ids' => $course->modules->pluck('id')->toArray(),
                'description' => "Sertifikat kelulusan utama atas penyelesaian seluruh kurikulum kelas " . $course->title . ".",
                'is_active' => true,
            ]);

            $certificates = Certificate::where('course_id', $course->id)
                ->where('is_active', true)
                ->get();
        }

        $userClaimed = $user ? UserCertificate::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->get()
            ->keyBy('certificate_id') : collect();

        $certificatesData = $certificates->map(function ($cert) use ($user, $userClaimed) {
            $unlockStatus = $cert->getUnlockStatusForUser($user);
            $claimed = isset($userClaimed[$cert->id]);

            return [
                'id' => $cert->id,
                'title' => $cert->title,
                'type' => $cert->type,
                'module_ids' => $cert->module_ids,
                'description' => $cert->description,
                'unlocked' => $unlockStatus['unlocked'],
                'progress_count' => $unlockStatus['progress_count'],
                'total_required' => $unlockStatus['total_required'],
                'percentage' => $unlockStatus['percentage'],
                'reason' => $unlockStatus['reason'],
                'claimed' => $claimed,
                'certificate_code' => $claimed ? $userClaimed[$cert->id]->certificate_code : null,
                'claimed_at' => $claimed ? $userClaimed[$cert->id]->claimed_at->toIso8601String() : null,
            ];
        });

        return response()->json([
            'certificates' => $certificatesData,
        ]);
    }

    /**
     * Claim / Unlock a certificate for student
     */
    public function claim(Request $request, Course $course, Certificate $certificate)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($certificate->course_id !== $course->id) {
            return response()->json(['message' => 'Sertifikat tidak sesuai dengan kelas ini.'], 422);
        }

        $unlockStatus = $certificate->getUnlockStatusForUser($user);

        if (!$unlockStatus['unlocked']) {
            return response()->json([
                'message' => $unlockStatus['reason'],
                'unlock_status' => $unlockStatus
            ], 403);
        }

        // Generate or fetch claimed certificate
        $userCert = UserCertificate::firstOrCreate(
            [
                'user_id' => $user->id,
                'certificate_id' => $certificate->id,
            ],
            [
                'course_id' => $course->id,
                'certificate_code' => 'CERT-' . strtoupper(Str::random(4)) . '-' . rand(1000, 9999),
                'claimed_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Sertifikat berhasil diklaim dan dibuka!',
            'certificate_code' => $userCert->certificate_code,
            'user_certificate' => $userCert,
        ]);
    }

    /**
     * Store certificate template (Admin / Instructor in Course Builder)
     */
    public function store(Request $request, Course $course)
    {
        if (!auth()->user()->isAdmin() && $course->instructor_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|string|in:session,multi_session,course_completion',
            'module_ids' => 'required|array|min:1',
            'description' => 'nullable|string',
        ]);

        $cert = Certificate::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'type' => $request->type,
            'module_ids' => $request->module_ids,
            'description' => $request->description,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Template sertifikat berhasil dibuat',
            'certificate' => $cert
        ]);
    }

    /**
     * Delete certificate template
     */
    public function destroy(Certificate $certificate)
    {
        if (!auth()->user()->isAdmin() && $certificate->course->instructor_id !== auth()->id()) {
            abort(403);
        }

        $certificate->delete();

        return response()->json(['message' => 'Template sertifikat berhasil dihapus']);
    }

    /**
     * Show / Render Certificate Document Page (Session & Completion)
     */
    public function show(Request $request, string $code)
    {
        $user = auth()->user();

        // Search by user_certificate code or by cert id preview
        $userCert = UserCertificate::with(['user', 'course.instructor', 'certificate'])->where('certificate_code', $code)->first();

        if ($userCert) {
            $course = $userCert->course;
            $certTitle = $userCert->certificate ? $userCert->certificate->title : ("Sertifikat Kelulusan " . $course->title);
            $studentName = $userCert->user->name;
            $claimedAt = $userCert->claimed_at ? $userCert->claimed_at->translatedFormat('d F Y') : now()->translatedFormat('d F Y');
            $certCode = $userCert->certificate_code;
            $certType = $userCert->certificate ? $userCert->certificate->type : 'course_completion';
        } else {
            // Preview mode for Admin / Instructor
            $cert = Certificate::with(['course.instructor'])->find($code);
            if (!$cert) {
                // Fallback check course by slug
                $course = Course::where('slug', $code)->orWhere('id', $code)->firstOrFail();
                $certTitle = "Sertifikat Completion: " . $course->title;
                $certType = 'course_completion';
            } else {
                $course = $cert->course;
                $certTitle = $cert->title;
                $certType = $cert->type;
            }

            if (!$user || (!$user->isAdmin() && $user->id !== $course->instructor_id)) {
                abort(404, 'Sertifikat tidak ditemukan atau kode tidak valid.');
            }

            $studentName = $user->name;
            $claimedAt = now()->translatedFormat('d F Y');
            $certCode = 'PREVIEW-CERT-' . rand(1000, 9999);
        }

        $settings = [
            'cert_authorised_name' => \App\Models\Setting::where('key', 'cert_authorised_name')->value('value') ?: 'Management Drastha Learning',
            'cert_company_name' => \App\Models\Setting::where('key', 'cert_company_name')->value('value') ?: 'Drastha Learning Inc.',
            'cert_page' => \App\Models\Setting::where('key', 'cert_page')->value('value') ?: 'certificate',
            'cert_signature' => \App\Models\Setting::where('key', 'cert_signature')->value('value') ?: '/images/signature-placeholder.png',
            'cert_show_instructor' => filter_var(\App\Models\Setting::where('key', 'cert_show_instructor')->value('value') ?: 'true', FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('Courses/Certificate', [
            'course' => $course,
            'certificateTitle' => $certTitle,
            'certificateCode' => $certCode,
            'certificateType' => $certType,
            'settings' => $settings,
            'completedAt' => $claimedAt,
            'studentName' => $studentName,
        ]);
    }
}
