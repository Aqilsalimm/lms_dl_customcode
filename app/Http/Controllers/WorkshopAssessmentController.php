<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\WorkshopAssessment;
use App\Models\WorkshopAssessmentQuestion;
use App\Models\WorkshopAssessmentAttempt;
use App\Models\WorkshopAssessmentUserAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

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
            'use_global_settings' => 'nullable|boolean',
            'duration_minutes' => 'nullable|integer|min:1',
            'passing_score' => 'nullable|integer|min:0|max:100',
            'max_attempts' => 'nullable|integer|min:0',
            'is_published' => 'nullable|boolean',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'questions' => 'nullable|array',
            'questions.*.id' => 'nullable|integer',
            'questions.*.question_text' => 'required|string',
            'questions.*.options' => 'required|array|min:2',
            'questions.*.correct_answer' => 'required|string',
        ]);

        $useGlobal = $request->has('use_global_settings') ? filter_var($request->use_global_settings, FILTER_VALIDATE_BOOLEAN) : true;

        $defaultDuration = (int) (\App\Models\Setting::where('key', 'test_builder_default_duration')->value('value') ?: 30);
        $defaultPrePassing = (int) (\App\Models\Setting::where('key', 'test_builder_pre_passing_score')->value('value') ?: 70);
        $defaultPostPassing = (int) (\App\Models\Setting::where('key', 'test_builder_post_passing_score')->value('value') ?: 70);
        $defaultMaxAttempts = (int) (\App\Models\Setting::where('key', 'test_builder_default_max_attempts')->value('value') ?: 3);

        $passingScore = $request->passing_score ?? ($request->type === 'pre_test' ? $defaultPrePassing : $defaultPostPassing);

        $assessment = $course->assessments()->updateOrCreate(
            ['type' => $request->type],
            [
                'title' => $request->title,
                'description' => $request->description,
                'use_global_settings' => $useGlobal,
                'duration_minutes' => $request->duration_minutes ?? $defaultDuration,
                'passing_score' => $passingScore,
                'max_attempts' => $request->max_attempts ?? $defaultMaxAttempts,
                'is_published' => $request->is_published ?? false,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
            ]
        );

        // Sync questions if provided in the payload
        if ($request->has('questions')) {
            $savedIds = [];
            foreach ($request->input('questions') as $index => $qData) {
                $qId = $qData['id'] ?? null;
                $question = $assessment->questions()->updateOrCreate(
                    ['id' => $qId],
                    [
                        'question_text' => $qData['question_text'],
                        'options' => $qData['options'],
                        'correct_answer' => $qData['correct_answer'],
                        'points' => $qData['points'] ?? 10,
                        'order_number' => $index,
                    ]
                );
                $savedIds[] = $question->id;
            }
            // Delete questions not included in the payload
            $assessment->questions()->whereNotIn('id', $savedIds)->delete();
        }

        return response()->json([
            'message' => 'Workshop assessment saved successfully',
            'assessment' => $assessment->load('questions'),
        ]);
    }

    /**
     * Bulk store or update pre-test and post-test configurations along with questions.
     */
    public function updateTestBuilder(Request $request, Course $course)
    {
        $this->validateCourseOwner($course);

        $validated = $request->validate([
            'module_id' => 'nullable|integer|exists:modules,id',
            'assessments' => 'required|array',
            'assessments.*.type' => 'required|string|in:pre_test,post_test',
            'assessments.*.title' => 'required|string|max:255',
            'assessments.*.description' => 'nullable|string',
            'assessments.*.use_global_settings' => 'nullable|boolean',
            'assessments.*.duration_minutes' => 'nullable|integer|min:1',
            'assessments.*.passing_score' => 'nullable|integer|min:0|max:100',
            'assessments.*.max_attempts' => 'nullable|integer|min:0',
            'assessments.*.questions' => 'nullable|array',
            'assessments.*.questions.*.question_text' => 'required|string',
            'assessments.*.questions.*.options' => 'required|array|min:2',
            'assessments.*.questions.*.correct_answer' => 'required|string',
            'assessments.*.questions.*.points' => 'nullable|integer|min:1',
        ]);

        $targetModuleId = $request->input('module_id');

        DB::transaction(function () use ($course, $validated, $targetModuleId) {
            $defaultDuration = (int) (\App\Models\Setting::where('key', 'test_builder_default_duration')->value('value') ?: 30);
            $defaultPrePassing = (int) (\App\Models\Setting::where('key', 'test_builder_pre_passing_score')->value('value') ?: 70);
            $defaultPostPassing = (int) (\App\Models\Setting::where('key', 'test_builder_post_passing_score')->value('value') ?: 70);
            $defaultMaxAttempts = (int) (\App\Models\Setting::where('key', 'test_builder_default_max_attempts')->value('value') ?: 3);

            foreach ($validated['assessments'] as $assessmentData) {
                $modId = $assessmentData['module_id'] ?? $targetModuleId;
                $passingScore = $assessmentData['passing_score'] ?? ($assessmentData['type'] === 'pre_test' ? $defaultPrePassing : $defaultPostPassing);
                $useGlobal = isset($assessmentData['use_global_settings']) ? filter_var($assessmentData['use_global_settings'], FILTER_VALIDATE_BOOLEAN) : true;

                // A. Update or Create assessment (pre_test / post_test)
                $assessment = $course->assessments()->updateOrCreate(
                    [
                        'type' => $assessmentData['type'],
                        'module_id' => $modId,
                    ],
                    [
                        'title' => $assessmentData['title'],
                        'description' => $assessmentData['description'] ?? null,
                        'use_global_settings' => $useGlobal,
                        'duration_minutes' => $assessmentData['duration_minutes'] ?? $defaultDuration,
                        'passing_score' => $passingScore,
                        'max_attempts' => $assessmentData['max_attempts'] ?? $defaultMaxAttempts,
                        'is_published' => true, // default published when configured via builder
                    ]
                );

                // B. Sync Questions (Delete & Bulk Insert)
                if (isset($assessmentData['questions'])) {
                    // Delete old questions (foreign key constraints cascadeOnDelete answers automatically)
                    $assessment->questions()->delete();

                    $questionsToInsert = [];
                    foreach ($assessmentData['questions'] as $index => $q) {
                        $questionsToInsert[] = [
                            'assessment_id' => $assessment->id,
                            'question_text' => $q['question_text'],
                            'options' => json_encode($q['options']),
                            'correct_answer' => $q['correct_answer'],
                            'points' => $q['points'] ?? 10,
                            'order_number' => $index + 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }

                    if (count($questionsToInsert) > 0) {
                        $assessment->questions()->insert($questionsToInsert);
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Setup Pre-test & Post-test berhasil disimpan!');
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

        $effectiveMaxAttempts = $assessment->effective_max_attempts;
        if ($effectiveMaxAttempts > 0 && $attemptCount >= $effectiveMaxAttempts) {
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
        $passingScore = $assessment->passing_score;
        if ($passingScore <= 0) {
            $settingKey = ($assessment->type === 'pre_test') ? 'test_builder_pre_passing_score' : 'test_builder_post_passing_score';
            $passingScore = (int) (\App\Models\Setting::where('key', $settingKey)->value('value') ?: 70);
        }
        $isPassed = ($score >= $passingScore);

        $attempt->update([
            'status' => 'completed',
            'total_score' => $score,
            'is_passed' => $isPassed,
            'completed_at' => Carbon::now(),
        ]);

        // Dispatch Broadcast Event for Real-Time Exam Report Analytics
        try {
            event(new \App\Events\ExamAttemptSubmitted($attempt));
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Broadcast event ExamAttemptSubmitted failed: ' . $e->getMessage());
        }

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
    public function getLiveMeetingLink(Request $request, Course $course)
    {
        $user = auth()->user();

        // 1. Authorization checks (Admin, Instructor, and Enrolled Students only)
        if (!$user->isAdmin() && $course->instructor_id !== $user->id && !$user->hasEnrolled($course->id)) {
            abort(403, 'Anda tidak terdaftar di kelas ini.');
        }

        $moduleId = $request->query('module_id');
        $module = $moduleId ? $course->modules()->find($moduleId) : null;
        $targetMeetingUrl = ($module && $module->meeting_url) ? $module->meeting_url : $course->meeting_url;
        $enforcePrerequisites = filter_var(
            \App\Models\Setting::where('key', 'test_builder_enforce_prerequisites')->value('value') ?: 'true',
            FILTER_VALIDATE_BOOLEAN
        );

        // 2. Only apply pre-test & prerequisite lock for live classes if restricted mode is enabled
        if ($course->course_type === 'live_class' && $enforcePrerequisites) {
            if (!$user->isAdmin() && $course->instructor_id !== $user->id) {
                if ($module) {
                    // Check previous module post-test prerequisite
                    $prevModule = $course->modules()
                        ->where('sort_order', '<', $module->sort_order)
                        ->orderByDesc('sort_order')
                        ->first();

                    if ($prevModule) {
                        $prevPostTest = $prevModule->assessments()->where('type', 'post_test')->first();
                        if ($prevPostTest && $prevPostTest->is_published) {
                            $hasPassedPrev = $prevPostTest->attempts()
                                ->where('user_id', $user->id)
                                ->where('is_passed', true)
                                ->exists();
                            if (!$hasPassedPrev) {
                                return redirect()->back()->with('error', "Anda wajib menyelesaikan Post-test sesi sebelumnya ({$prevModule->title}) terlebih dahulu.");
                            }
                        }
                    }

                    // Check current module pre-test
                    $modulePreTest = $module->assessments()->where('type', 'pre_test')->first();
                    if ($modulePreTest && $modulePreTest->is_published) {
                        $completed = $modulePreTest->attempts()
                            ->where('user_id', $user->id)
                            ->where('status', 'completed')
                            ->exists();

                        if (!$completed) {
                            return redirect()->back()->with('error', 'Anda wajib menyelesaikan Pre-test sesi ini terlebih dahulu.');
                        }
                    }
                } else {
                    $preTest = $course->assessments()->where('type', 'pre_test')->first();
                    if ($preTest && $preTest->is_published) {
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
        }

        // 3. Serve the link if check succeeds or bypasses
        if (!$targetMeetingUrl) {
            return redirect()->back()->with('error', 'Tautan pertemuan belum tersedia untuk sesi ini.');
        }

        return redirect()->away($targetMeetingUrl);
    }

    /**
     * Display the student assessment (pre-test/post-test) execution page.
     */
    public function showStudentAssessment(Course $course, WorkshopAssessment $assessment)
    {
        $user = auth()->user();

        // 1. Authorization check
        if (!$user->isAdmin() && $course->instructor_id !== $user->id && !$user->hasEnrolled($course->id)) {
            abort(403, 'Anda tidak terdaftar di kelas ini.');
        }

        // Ensure the assessment belongs to the course
        if ($assessment->course_id !== $course->id) {
            abort(404);
        }

        // 2. Load questions
        $assessment->load(['questions' => function ($query) {
            $query->orderBy('order_number')->orderBy('id');
        }]);

        // Hide correct_answer key in the returned Inertia props for student
        $assessment->questions->each(function ($question) use ($user, $course) {
            if (!$user->isAdmin() && $course->instructor_id !== $user->id) {
                $question->makeHidden(['correct_answer']);
            }
        });

        // 3. Find any existing in_progress attempt
        $existingAttempt = $assessment->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        // 4. Also fetch past attempts to show history/retake options
        $pastAttempts = $assessment->attempts()
            ->where('user_id', $user->id)
            ->orderBy('attempt_number', 'desc')
            ->get();

        // Eager load full course syllabus for the left sidebar
        $course->load([
            'category',
            'instructor',
            'modules.lessons' => function ($q) {
                $q->orderBy('sort_order')->orderBy('id');
            },
            'assessments' => function ($q) {
                $q->where('is_published', true);
            }
        ]);

        return Inertia::render('Courses/Assessment', [
            'course' => $course,
            'assessment' => $assessment,
            'existingAttempt' => $existingAttempt,
            'pastAttempts' => $pastAttempts,
        ]);
    }

    /**
     * Display analytical dashboard for Pre-Test and Post-Test recapitulation.
     */
    public function analytics($course = null)
    {
        $user = auth()->user();

        if ($course) {
            if (is_numeric($course) || is_string($course)) {
                $course = Course::where('id', $course)->orWhere('slug', $course)->first();
            }
        }

        $coursesQuery = $user->isAdmin() ? Course::query() : Course::where('instructor_id', $user->id);
        $allCourses = $coursesQuery->select(['id', 'title', 'slug'])->get();

        if (!$course || !$course->exists) {
            $course = (clone $coursesQuery)->whereHas('assessments')->first() ?: (clone $coursesQuery)->first();
            if (!$course) {
                return redirect()->route('course-builder.index')->with('warning', 'Belum ada kelas dengan Ujian/Assessment untuk ditampilkan dalam laporan.');
            }
        }

        $this->validateCourseOwner($course);

        $preTest = $course->assessments()->where('type', 'pre_test')->first();
        $postTest = $course->assessments()->where('type', 'post_test')->first();

        // 1. Aggregate Metrics
        $preTestAvg = 0;
        $preTestPassCount = 0;
        $preTestTotalAttempts = 0;

        if ($preTest) {
            $completedPre = $preTest->attempts()->where('status', 'completed');
            $preTestAvg = round($completedPre->avg('total_score') ?? 0, 1);
            $preTestPassCount = (clone $completedPre)->where('is_passed', true)->count();
            $preTestTotalAttempts = $completedPre->count();
        }

        $postTestAvg = 0;
        $postTestPassCount = 0;
        $postTestTotalAttempts = 0;

        if ($postTest) {
            $completedPost = $postTest->attempts()->where('status', 'completed');
            $postTestAvg = round($completedPost->avg('total_score') ?? 0, 1);
            $postTestPassCount = (clone $completedPost)->where('is_passed', true)->count();
            $postTestTotalAttempts = $completedPost->count();
        }

        // 2. Item Analysis (Top 5 Hardest Questions)
        $preTestHardest = [];
        if ($preTest) {
            $preTestHardest = WorkshopAssessmentQuestion::where('assessment_id', $preTest->id)
                ->withCount(['userAnswers as wrong_answers_count' => function ($q) {
                    $q->where('is_correct', false);
                }])
                ->withCount(['userAnswers as total_answers_count'])
                ->orderByDesc('wrong_answers_count')
                ->take(5)
                ->get();
        }

        $postTestHardest = [];
        if ($postTest) {
            $postTestHardest = WorkshopAssessmentQuestion::where('assessment_id', $postTest->id)
                ->withCount(['userAnswers as wrong_answers_count' => function ($q) {
                    $q->where('is_correct', false);
                }])
                ->withCount(['userAnswers as total_answers_count'])
                ->orderByDesc('wrong_answers_count')
                ->take(5)
                ->get();
        }

        // 3. At-Risk Student Flagging
        $atRiskStudents = [];
        if ($preTest || $postTest) {
            $assessmentIds = array_values(array_filter([$preTest?->id, $postTest?->id]));
            
            $atRiskAttempts = WorkshopAssessmentAttempt::with(['user', 'assessment'])
                ->whereIn('assessment_id', $assessmentIds)
                ->where('status', 'completed')
                ->where('is_passed', false)
                ->get()
                ->groupBy('user_id');

            foreach ($atRiskAttempts as $userId => $attempts) {
                $user = $attempts->first()->user;
                $failedCount = $attempts->count();
                $lastAttempt = $attempts->sortByDesc('created_at')->first();
                $assessment = $lastAttempt->assessment;

                if ($failedCount >= 2 || ($assessment->max_attempts > 0 && $failedCount >= $assessment->max_attempts)) {
                    $atRiskStudents[] = [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'assessment_title' => $assessment->title,
                        'assessment_type' => $assessment->type,
                        'failed_attempts' => $failedCount,
                        'max_attempts' => $assessment->max_attempts,
                        'last_score' => $lastAttempt->total_score,
                        'last_attempt_at' => $lastAttempt->updated_at ? $lastAttempt->updated_at->format('d M Y, H:i') : '-',
                    ];
                }
            }
        }

        // 4. Student Progress Table (All enrolled students and scores)
        $enrolledStudents = $course->enrollments()->with('user')->get()->map(function ($enrollment) use ($preTest, $postTest) {
            $user = $enrollment->user;

            $preScore = null;
            $preStatus = 'Belum Mengambil';
            if ($preTest) {
                $bestPre = $preTest->attempts()
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->orderByDesc('total_score')
                    ->first();
                if ($bestPre) {
                    $preScore = $bestPre->total_score;
                    $preStatus = $bestPre->is_passed ? 'Lulus' : 'Gagal';
                }
            }

            $postScore = null;
            $postStatus = 'Belum Mengambil';
            if ($postTest) {
                $bestPost = $postTest->attempts()
                    ->where('user_id', $user->id)
                    ->where('status', 'completed')
                    ->orderByDesc('total_score')
                    ->first();
                if ($bestPost) {
                    $postScore = $bestPost->total_score;
                    $postStatus = $bestPost->is_passed ? 'Lulus' : 'Gagal';
                }
            }

            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'pre_test_score' => $preScore,
                'pre_test_status' => $preStatus,
                'post_test_score' => $postScore,
                'post_test_status' => $postStatus,
                'enrolled_at' => $enrollment->created_at ? $enrollment->created_at->format('d M Y') : '-',
            ];
        });

        return Inertia::render('Dashboard/Instructor/AssessmentAnalytics', [
            'course' => $course->only(['id', 'title', 'slug', 'course_type']),
            'allCourses' => $allCourses,
            'preTest' => $preTest ? [
                'id' => $preTest->id,
                'title' => $preTest->title,
                'passing_score' => $preTest->passing_score,
                'avg_score' => $preTestAvg,
                'pass_count' => $preTestPassCount,
                'total_attempts' => $preTestTotalAttempts,
                'hardest_questions' => $preTestHardest,
            ] : null,
            'postTest' => $postTest ? [
                'id' => $postTest->id,
                'title' => $postTest->title,
                'passing_score' => $postTest->passing_score,
                'avg_score' => $postTestAvg,
                'pass_count' => $postTestPassCount,
                'total_attempts' => $postTestTotalAttempts,
                'hardest_questions' => $postTestHardest,
            ] : null,
            'atRiskStudents' => array_values($atRiskStudents),
            'studentScores' => $enrolledStudents,
        ]);
    }
}
