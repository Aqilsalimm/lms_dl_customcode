<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentUserAnswer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ExamReportController extends Controller
{
    /**
     * Display the Exam & Assessment Report Analytics dashboard.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. Role Authorization
        if (!$user->isAdmin() && !$user->isInstructor()) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized access to Exam Reports.');
        }

        // 2. Base Query & Filters
        $filters = $request->only([
            'date_preset', 'course_id', 'assessment_type', 'status', 'search', 'start_date', 'end_date'
        ]);
        
        $datePreset = $filters['date_preset'] ?? '30_days';
        $courseId = $filters['course_id'] ?? null;
        $type = $filters['assessment_type'] ?? 'all';
        $status = $filters['status'] ?? 'all';
        $search = trim($filters['search'] ?? '');

        // Available Courses for Filter Dropdown
        $coursesQuery = Course::query();
        if (!$user->isAdmin()) {
            $coursesQuery->where('instructor_id', $user->id);
        }
        $coursesList = $coursesQuery->select('id', 'title')->orderBy('title')->get();
        $instructorCourseIds = $user->isAdmin() ? null : $coursesList->pluck('id')->toArray();

        $repository = new \App\Repositories\ExamReportRepository();
        $dateRange = $repository->calculateDateRange($datePreset, $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $startDate = $dateRange['start'];
        $endDate = $dateRange['end'];

        $allFilteredAttempts = $repository->getFilteredAttempts($filters, $instructorCourseIds);


        // 3. Compute Executive Summary KPIs
        $totalVolume = $allFilteredAttempts->count();
        $passedCount = $allFilteredAttempts->where('is_passed', true)->count();
        $passRate = $totalVolume > 0 ? round(($passedCount / $totalVolume) * 100, 1) : 0;
        $avgScore = $totalVolume > 0 ? round($allFilteredAttempts->avg('total_score'), 1) : 0;

        // Anomaly / Flagged count
        $flaggedAttempts = $allFilteredAttempts->filter(function ($att) {
            $durationSec = ($att->started_at && $att->completed_at) ? $att->started_at->diffInSeconds($att->completed_at) : 999;
            return $att->total_score < 40 || $durationSec < 30;
        });
        $flaggedCount = $flaggedAttempts->count();

        // Average Duration in Minutes
        $totalDurationSec = 0;
        $durationCount = 0;
        foreach ($allFilteredAttempts as $att) {
            if ($att->started_at && $att->completed_at) {
                $totalDurationSec += $att->started_at->diffInSeconds($att->completed_at);
                $durationCount++;
            }
        }
        $avgDurationMin = $durationCount > 0 ? round(($totalDurationSec / $durationCount) / 60, 1) : 0;

        // Previous Period Trends Comparison
        $prevTrend = ['volume' => 0, 'pass_rate' => 0, 'status' => 'neutral'];
        if ($startDate) {
            $periodDays = max(1, $startDate->diffInDays($endDate));
            $prevStartDate = $startDate->copy()->subDays($periodDays);
            $prevEndDate = $startDate->copy()->subSecond();

            $prevAttemptsQuery = WorkshopAssessmentAttempt::where('status', 'completed')
                ->whereHas('assessment', function ($q) use ($instructorCourseIds, $courseId, $type) {
                    if ($instructorCourseIds !== null) {
                        $q->whereIn('course_id', $instructorCourseIds);
                    }
                    if ($courseId) {
                        $q->where('course_id', $courseId);
                    }
                    if ($type && $type !== 'all') {
                        $q->where('type', $type);
                    }
                })
                ->whereBetween('completed_at', [$prevStartDate, $prevEndDate]);

            $prevVolume = $prevAttemptsQuery->count();
            $prevPassed = (clone $prevAttemptsQuery)->where('is_passed', true)->count();
            $prevPassRate = $prevVolume > 0 ? round(($prevPassed / $prevVolume) * 100, 1) : 0;

            $volumeDiff = $totalVolume - $prevVolume;
            $volumePercent = $prevVolume > 0 ? round(($volumeDiff / $prevVolume) * 100, 1) : ($totalVolume > 0 ? 100 : 0);
            $passRateDiff = round($passRate - $prevPassRate, 1);

            $prevTrend = [
                'volume_diff' => ($volumeDiff >= 0 ? '+' : '') . $volumeDiff,
                'volume_percent' => ($volumePercent >= 0 ? '+' : '') . $volumePercent . '%',
                'pass_rate_diff' => ($passRateDiff >= 0 ? '+' : '') . $passRateDiff . '%',
                'pass_rate_status' => $passRateDiff >= 0 ? 'positive' : 'negative'
            ];
        }

        // 4. Visual Analytics: Trend Chart (Grouped by Date)
        $trendChartData = [];
        $groupedByDate = $allFilteredAttempts->groupBy(function ($item) {
            return $item->completed_at ? $item->completed_at->format('Y-m-d') : now()->format('Y-m-d');
        });

        foreach ($groupedByDate as $dateStr => $items) {
            $cnt = $items->count();
            $pCnt = $items->where('is_passed', true)->count();
            $pr = $cnt > 0 ? round(($pCnt / $cnt) * 100, 1) : 0;
            $avgSc = $cnt > 0 ? round($items->avg('total_score'), 1) : 0;

            $trendChartData[] = [
                'date' => Carbon::parse($dateStr)->format('d M'),
                'raw_date' => $dateStr,
                'attempts' => $cnt,
                'passed' => $pCnt,
                'pass_rate' => $pr,
                'avg_score' => $avgSc,
            ];
        }
        usort($trendChartData, fn($a, $b) => strcmp($a['raw_date'], $b['raw_date']));

        // 5. Visual Analytics: Distribution Chart (Grade Clusters & Status)
        $gradeTiers = [
            'excellent' => $allFilteredAttempts->where('total_score', '>=', 85)->count(),
            'good' => $allFilteredAttempts->filter(fn($a) => $a->total_score >= 70 && $a->total_score < 85)->count(),
            'remedial' => $allFilteredAttempts->where('total_score', '<', 70)->count(),
        ];

        $distributionData = [
            'passed' => $passedCount,
            'failed' => $totalVolume - $passedCount,
            'flagged' => $flaggedCount,
            'tiers' => $gradeTiers,
        ];

        // 6. Comparison Matrix (Performance breakdown by Course)
        $comparisonMatrix = [];
        $groupedByCourse = $allFilteredAttempts->groupBy(function ($item) {
            return $item->assessment->course->title ?? 'Unassigned Course';
        });

        foreach ($groupedByCourse as $courseTitle => $items) {
            $cnt = $items->count();
            $pCnt = $items->where('is_passed', true)->count();
            $pr = $cnt > 0 ? round(($pCnt / $cnt) * 100, 1) : 0;
            $avgSc = $cnt > 0 ? round($items->avg('total_score'), 1) : 0;

            $comparisonMatrix[] = [
                'course_title' => $courseTitle,
                'total_attempts' => $cnt,
                'passed_count' => $pCnt,
                'pass_rate' => $pr,
                'avg_score' => $avgSc,
            ];
        }
        usort($comparisonMatrix, fn($a, $b) => $b['total_attempts'] <=> $a['total_attempts']);

        // 7. Anomaly Highlights & Actionable Insights
        $anomalyFlags = [];
        $actionableInsights = [];

        // Check for rapid attempts (<30s)
        $rapidAttempts = $allFilteredAttempts->filter(function ($att) {
            return $att->started_at && $att->completed_at && $att->started_at->diffInSeconds($att->completed_at) < 30;
        });

        if ($rapidAttempts->count() > 0) {
            $anomalyFlags[] = [
                'type' => 'rapid_attempt',
                'level' => 'warning',
                'title' => 'Deteksi Pengerjaan Abnormal (< 30 Detik)',
                'message' => "Terdeteksi {$rapidAttempts->count()} percobaan tes selesai dalam durasi kurang dari 30 detik. Berpotensi indikasi pengisian acak atau kecurangan.",
                'count' => $rapidAttempts->count(),
            ];
            $actionableInsights[] = 'Evaluasi durasi minimal pengerjaan dan batasi pengerjaan ulang otomatis untuk peserta yang menyelesaikan tes di bawah 30 detik.';
        }

        // Check for low pass rate courses
        foreach ($comparisonMatrix as $cm) {
            if ($cm['total_attempts'] >= 3 && $cm['pass_rate'] < 50) {
                $anomalyFlags[] = [
                    'type' => 'low_pass_rate',
                    'level' => 'danger',
                    'title' => "Tingkat Kelulusan Rendah: {$cm['course_title']}",
                    'message' => "Kursus \"{$cm['course_title']}\" memiliki tingkat kelulusan hanya {$cm['pass_rate']}% dari {$cm['total_attempts']} percobaan.",
                    'count' => $cm['total_attempts'],
                ];
                $actionableInsights[] = "Lakukan peninjauan bobot soal dan materi untuk \"{$cm['course_title']}\" atau jadwalkan sesi pemantapan/Live Class remedial.";
            }
        }

        if (empty($anomalyFlags)) {
            $actionableInsights[] = 'Seluruh indikator kinerja tes berada dalam batas normal. Pertahankan kualitas materi dan bobot penilaian.';
        }

        // 8. Granular Breakdown Table Format
        $tableData = $allFilteredAttempts->take(100)->map(function ($att) {
            $durationSec = ($att->started_at && $att->completed_at) ? $att->started_at->diffInSeconds($att->completed_at) : 0;
            $durationFormatted = sprintf('%dm %ds', floor($durationSec / 60), $durationSec % 60);

            $isFlagged = ($att->total_score < 40) || ($durationSec < 30);

            return [
                'id' => $att->id,
                'attempt_code' => '#EXM-' . str_pad($att->id, 5, '0', STR_PAD_LEFT),
                'student_name' => $att->user->name ?? 'Deleted User',
                'student_email' => $att->user->email ?? '-',
                'student_photo' => $att->user->photo ?? null,
                'course_title' => $att->assessment->course->title ?? 'General Course',
                'module_title' => $att->assessment->module->title ?? '-',
                'type' => $att->assessment->type ?? 'post_test',
                'attempt_number' => $att->attempt_number ?? 1,
                'total_score' => (float) $att->total_score,
                'is_passed' => (bool) $att->is_passed,
                'is_flagged' => $isFlagged,
                'duration_formatted' => $durationFormatted,
                'duration_seconds' => $durationSec,
                'completed_at_formatted' => $att->completed_at ? $att->completed_at->format('d M Y, H:i') : '-',
                'completed_at_iso' => $att->completed_at ? $att->completed_at->toIso8601String() : null,
                'answers_count' => $att->userAnswers->count(),
                'answers_breakdown' => $att->userAnswers->map(function ($ans) {
                    return [
                        'question_text' => $ans->question->question_text ?? 'Soal',
                        'selected_answer' => $ans->selected_answer,
                        'is_correct' => (bool) $ans->is_correct,
                        'points' => $ans->question->points ?? 10,
                    ];
                }),
            ];
        });

        // 9. Period Label for Meta
        $periodLabel = 'Keseluruhan Data';
        if ($startDate && $endDate) {
            $periodLabel = $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y');
        }

        // 10. Audit Metadata
        $auditMetadata = [
            'generated_at' => now()->format('d M Y, H:i:s') . ' WIB',
            'generated_by' => $user->name . ' (' . ucfirst($user->role) . ')',
            'ip_address' => $request->ip(),
            'period_label' => $periodLabel,
        ];

        return Inertia::render('Dashboard/Reports/ExamReport', [
            'filters' => [
                'date_preset' => $datePreset,
                'start_date' => $request->start_date ?? '',
                'end_date' => $request->end_date ?? '',
                'course_id' => $courseId ?? '',
                'assessment_type' => $type,
                'status' => $status,
                'search' => $search,
            ],
            'coursesList' => $coursesList,
            'kpiMetrics' => [
                'total_volume' => $totalVolume,
                'pass_rate' => $passRate,
                'avg_score' => $avgScore,
                'flagged_count' => $flaggedCount,
                'avg_duration_min' => $avgDurationMin,
                'prev_trend' => $prevTrend,
            ],
            'trendChart' => $trendChartData,
            'distributionData' => $distributionData,
            'comparisonMatrix' => $comparisonMatrix,
            'anomalyFlags' => $anomalyFlags,
            'actionableInsights' => $actionableInsights,
            'tableData' => $tableData,
            'auditMetadata' => $auditMetadata,
        ]);
    }
}

