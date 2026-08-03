<?php

namespace App\Repositories;

use App\Models\WorkshopAssessmentAttempt;
use Carbon\Carbon;

class ExamReportRepository
{
    /**
     * Get filtered attempts based on provided filters.
     * Uses eager loading and query scopes for optimization.
     *
     * @param array $filters
     * @param array|null $instructorCourseIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getFilteredAttempts(array $filters, ?array $instructorCourseIds = null)
    {
        $datePreset = $filters['date_preset'] ?? '30_days';
        $courseId = $filters['course_id'] ?? null;
        $type = $filters['assessment_type'] ?? 'all';
        $status = $filters['status'] ?? 'all';
        $search = trim($filters['search'] ?? '');
        $startDate = $filters['start_date'] ?? null;
        $endDate = $filters['end_date'] ?? null;

        $dateRange = $this->calculateDateRange($datePreset, $startDate, $endDate);

        $query = WorkshopAssessmentAttempt::with([
            'user:id,name,email,photo,role',
            'assessment:id,course_id,module_id,title,type,passing_score',
            'assessment.course:id,title',
            'assessment.module:id,title',
            'userAnswers.question:id,question_text,correct_answer,points'
        ])
        ->where('status', 'completed')
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
        });

        if ($dateRange['start']) {
            $query->whereBetween('completed_at', [$dateRange['start'], $dateRange['end']]);
        }

        if ($status === 'passed') {
            $query->where('is_passed', true);
        } elseif ($status === 'failed') {
            $query->where('is_passed', false);
        } elseif ($status === 'flagged') {
            $query->where(function ($q) {
                $q->where('total_score', '<', 40)
                  ->orWhereRaw('TIMESTAMPDIFF(SECOND, started_at, completed_at) < 30');
            });
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('id', 'like', "%{$search}%");
            });
        }

        return $query->latest('completed_at')->get();
    }

    /**
     * Calculate date range based on preset.
     */
    public function calculateDateRange($preset, $customStart = null, $customEnd = null)
    {
        $now = Carbon::now();
        $start = null;
        $end = $now->copy()->endOfDay();

        switch ($preset) {
            case '7_days':
                $start = $now->copy()->subDays(7)->startOfDay();
                break;
            case '30_days':
                $start = $now->copy()->subDays(30)->startOfDay();
                break;
            case 'this_month':
                $start = $now->copy()->startOfMonth();
                break;
            case 'this_quarter':
                $start = $now->copy()->startOfQuarter();
                break;
            case 'custom':
                if ($customStart) {
                    $start = Carbon::parse($customStart)->startOfDay();
                }
                if ($customEnd) {
                    $end = Carbon::parse($customEnd)->endOfDay();
                }
                break;
            case 'all':
            default:
                $start = null;
                break;
        }

        return ['start' => $start, 'end' => $end];
    }
}
