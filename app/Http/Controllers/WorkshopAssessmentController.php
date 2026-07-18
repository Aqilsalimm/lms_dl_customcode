<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentUserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class WorkshopAssessmentController extends Controller
{
    /**
     * Helper to validate course ownership (instructor or admin).
     */
    protected function validateCourseOwner(Course $course)
    {
        $user = auth()->user();
        if (!$user->isAdmin() && $course->instructor_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
    }

    /**
     * Store or update an assessment (pre_test or post_test) for a course.
     */
    public function storeOrUpdate(Request $request, Course $course)
    {
        $this->validateCourseOwner($course);

        $request->validate([
            'type' => 'required|string|in:pre_test,post_test',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
        ]);

        $assessment = $course->assessments()->updateOrCreate(
            ['type' => $request->type],
            [
                'title' => $request->title,
                'description' => $request->description,
                'duration_minutes' => $request->duration_minutes,
                'passing_score' => $request->passing_score ?? 0,
                'max_attempts' => $request->max_attempts ?? 1,
                'is_published' => $request->is_published ?? false,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]
        );

        return response()->json([
            'message' => 'Workshop assessment saved successfully',
            'assessment' => $assessment->load('questions'),
        ]);
    }

    /**
     * Add a question to an assessment.
     */
    public function addQuestion(Request $request, WorkshopAssessment $assessment)
    {
        $this->validateCourseOwner($assessment->course);

        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'correct_answer' => 'required|string',
            'points' => 'nullable|integer|min:1',
            'order_number' => 'nullable|integer',
        ]);

        $question = $assessment->questions()->create([
            'question_text' => $request->question_text,
            'options' => $request->options,
            'correct_answer' => $request->correct_answer,
            'points' => $request->points ?? 10,
            'order_number' => $request->order_number ?? 0,
        ]);

        return response()->json([
            'message' => 'Question added successfully',
            'question' => $question,
        ]);
    }

    /**
     * Update an assessment question.
     */
    public function updateQuestion(Request $request, WorkshopAssessmentQuestion $question)
    {
        $this->validateCourseOwner($question->assessment->course);

        $request->validate([
            'question_text' => 'required|string',
            'options' => 'required|array|min:2',
            'correct_answer' => 'required|string',
            'points' => 'nullable|integer|min:1',
            'order_number' => 'nullable|integer',
        ]);

        $question->update([
            'question_text' => $request->question_text,
            'options' => $request->options,
            'correct_answer' => $request->correct_answer,
            'points' => $request->points ?? 10,
            'order_number' => $request->order_number ?? 0,
        ]);

        return response()->json([
            'message' => 'Question updated successfully',
            'question' => $question,
        ]);
    }

    /**
     * Delete an assessment question.
     */
    public function deleteQuestion(WorkshopAssessmentQuestion $question)
    {
        $this->validateCourseOwner($question->assessment->course);

        $question->delete();

        return response()->json([
            'message' => 'Question deleted successfully',
        ]);
    }

    /**
     * Start a new attempt on an assessment.
     */
    public function startAttempt(WorkshopAssessment $assessment)
    {
        $user = auth()->user();

        if (!$assessment->is_published) {
            abort(403, 'Tes ini belum diterbitkan.');
        }

        // Validate time window if set
        $now = Carbon::now();
        if ($assessment->start_time && $now->lessThan($assessment->start_time)) {
            return response()->json(['message' => 'Tes ini belum dapat diakses.'], 403);
        }
        if ($assessment->end_time && $now->greaterThan($assessment->end_time)) {
            return response()->json(['message' => 'Tes ini sudah ditutup.'], 403);
        }

        // 1. Cek Status Kelulusan: Jika peserta sudah pernah lulus di percobaan sebelumnya, cegah ambil tes lagi.
        $hasPassed = $assessment->attempts()
            ->where('user_id', $user->id)
            ->where('is_passed', true)
            ->exists();

        if ($hasPassed) {
            return response()->json(['message' => 'Anda sudah lulus tes ini dan tidak perlu mengulangnya.'], 403);
        }

        // Cek jika ada pengerjaan yang sedang berlangsung
        $inProgress = $assessment->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($inProgress) {
            return response()->json([
                'message' => 'Attempt already in progress',
                'attempt' => $inProgress,
            ]);
        }

        // 2. Cek Kuota Percobaan: Hitung jumlah percobaan yang sudah dilakukan
        $attemptCount = $assessment->attempts()
            ->where('user_id', $user->id)
            ->count();

        if ($assessment->max_attempts > 0 && $attemptCount >= $assessment->max_attempts) {
            return response()->json(['message' => 'Batas maksimal percobaan pengerjaan tes ini sudah habis.'], 403);
        }

        // 3. Mulai Sesi Baru: Set attempt_number = attemptCount + 1
        $attempt = $assessment->attempts()->create([
            'user_id' => $user->id,
            'status' => 'in_progress',
            'attempt_number' => $attemptCount + 1,
            'started_at' => $now,
        ]);

        return response()->json([
            'message' => 'Attempt started successfully',
            'attempt' => $attempt,
        ]);
    }

    /**
     * Submit an attempt, calculate score, and determine passing.
     */
    public function submitAttempt(Request $request, WorkshopAssessmentAttempt $attempt)
    {
        if ($attempt->status === 'completed') {
            return response()->json(['message' => 'Anda sudah menyelesaikan tes ini.'], 403);
        }

        $request->validate([
            'answers' => 'required|array', // key: question_id, value: selected_answer
        ]);

        $assessment = $attempt->assessment;
        $questions = $assessment->questions;

        $totalPointsScored = 0;
        $totalMaxPoints = 0;

        foreach ($questions as $question) {
            $totalMaxPoints += $question->points;
            $selectedAnswer = $request->answers[$question->id] ?? null;

            if ($selectedAnswer !== null) {
                $isCorrect = (strcasecmp(trim($selectedAnswer), trim($question->correct_answer)) === 0);

                if ($isCorrect) {
                    $totalPointsScored += $question->points;
                }

                WorkshopAssessmentUserAnswer::create([
                    'attempt_id' => $attempt->id,
                    'question_id' => $question->id,
                    'selected_answer' => $selectedAnswer,
                    'is_correct' => $isCorrect,
                ]);
            }
        }

        // Calculate final score percentage (0 - 100)
        $score = ($totalMaxPoints > 0) ? round(($totalPointsScored / $totalMaxPoints) * 100, 2) : 0;
        $isPassed = ($score >= $assessment->passing_score);

        $attempt->update([
            'status' => 'completed',
            'total_score' => $score,
            'is_passed' => $isPassed,
            'completed_at' => Carbon::now(),
        ]);

        return response()->json([
            'message' => 'Assessment submitted successfully',
            'score' => $score,
            'is_passed' => $isPassed,
            'attempt' => $attempt,
        ]);
    }

    /**
     * Validate pre-test completion before serving Zoom/Gmeet live link.
     */
    public function getLiveMeetingLink(Course $course)
    {
        $user = auth()->user();

        // 1. Authorization checks (Admin, Instructor, and Enrolled Students only)
        if (!$user->isAdmin() && $course->instructor_id !== $user->id && !$user->hasEnrolled($course->id)) {
            abort(403, 'Anda tidak terdaftar di kelas ini.');
        }

        // 2. Only apply pre-test lock for live classes
        if ($course->course_type === 'live_class') {
            $preTest = $course->assessments()->where('type', 'pre_test')->first();

            // If Pre-Test is configured and published, verify student has completed it
            if ($preTest && $preTest->is_published) {
                // Admin and the course instructor can bypass this restriction
                if (!$user->isAdmin() && $course->instructor_id !== $user->id) {
                    $completed = $preTest->attempts()
                        ->where('user_id', $user->id)
                        ->where('status', 'completed')
                        ->exists();

                    if (!$completed) {
                        return redirect()->back()->with('error', 'Anda wajib menyelesaikan Pre-test terlebih dahulu untuk mengakses tautan Zoom/pertemuan live.');
                    }
                }
            }
        }

        // 3. Serve the link if check succeeds or bypasses
        if (!$course->meeting_url) {
            return redirect()->back()->with('error', 'Tautan pertemuan belum tersedia.');
        }

        return redirect()->away($course->meeting_url);
    }
}
