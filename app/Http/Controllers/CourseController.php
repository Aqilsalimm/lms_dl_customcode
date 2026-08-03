<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CourseController extends Controller
{
    /**
     * Display a listing of public courses
     */
    public function index(Request $request)
    {
        // Check Course Visibility Setting
        $visibility = \App\Models\Setting::getValue('course_visibility');
        if (filter_var($visibility, FILTER_VALIDATE_BOOLEAN) && !auth()->check()) {
            return redirect()->to('/?login=true')->with('error', 'Please log in to view courses.');
        }

        $query = Course::where('status', 'published')
            ->select('id', 'title', 'slug', 'thumbnail', 'price', 'status', 'instructor_id', 'category_id', 'course_type', 'level', 'created_at')
            ->with(['category:id,name,slug', 'instructor:id,name,photo', 'lessons']);

        // Filter by Course Type (async / live_class)
        if ($request->has('type') && !empty($request->type) && $request->type !== 'Semua Mode' && $request->type !== 'all') {
            if ($request->type === 'live_class' || $request->type === 'Kelas Kursus / Live Class') {
                $query->where('course_type', 'live_class');
            } elseif ($request->type === 'async' || $request->type === 'Kursus Mandiri') {
                $query->where(function($q) {
                    $q->where('course_type', '!=', 'live_class')->orWhereNull('course_type');
                });
            } else {
                $query->where('course_type', $request->type);
            }
        }

        $levelMap = [
            'Kelas SD' => ['SD', 'Kelas SD'],
            'Kelas SMP' => ['SMP', 'Kelas SMP'],
            'Kelas SMA' => ['SMA', 'Kelas SMA'],
            'Umum / Profesional' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
            'Kelas Umum' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
            'SD' => ['SD', 'Kelas SD'],
            'SMP' => ['SMP', 'Kelas SMP'],
            'SMA' => ['SMA', 'Kelas SMA'],
            'Umum' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
            'Workshop' => ['Workshop', 'Kelas Workshop', 'workshop'],
            'workshop' => ['Workshop', 'Kelas Workshop', 'workshop'],
        ];

        $levelFilter = $request->get('level');
        $categoryFilter = $request->get('category');

        $isSameConcept = false;
        if (!empty($levelFilter) && !empty($categoryFilter) && $levelFilter !== 'Semua Kursus' && $categoryFilter !== 'semua' && $categoryFilter !== 'Semua Kursus') {
            $lvl = strtolower($levelFilter);
            $cat = strtolower($categoryFilter);
            if (($cat === 'sd' && str_contains($lvl, 'sd')) ||
                ($cat === 'smp' && str_contains($lvl, 'smp')) ||
                ($cat === 'sma' && str_contains($lvl, 'sma')) ||
                ($cat === 'umum' && str_contains($lvl, 'umum')) ||
                ($cat === 'workshop' && str_contains($lvl, 'workshop'))) {
                $isSameConcept = true;
            }
        }

        if ($isSameConcept) {
            // Apply combined OR query: Match category (including subcategories) OR level keyword
            $query->where(function($q) use ($categoryFilter, $levelFilter, $levelMap) {
                $cat = strtolower($categoryFilter);
                $targetCategory = Category::where('slug', $cat)->orWhere('name', 'like', "%{$cat}%")->first();
                $categoryIds = [];
                if ($targetCategory) {
                    $categoryIds[] = $targetCategory->id;
                    $subcatIds = Category::where('parent_id', $targetCategory->id)->pluck('id')->toArray();
                    $categoryIds = array_merge($categoryIds, $subcatIds);
                }

                $q->where(function($subQ) use ($categoryIds, $cat) {
                    if (!empty($categoryIds)) {
                        $subQ->whereIn('category_id', $categoryIds);
                    } else {
                        $subQ->whereHas('category', function($cq) use ($cat) {
                            $cq->where('slug', $cat)->orWhere('name', 'like', "%{$cat}%");
                        });
                    }
                });

                $dbLevels = $levelMap[$levelFilter] ?? [$levelFilter];
                $q->orWhereIn('level', $dbLevels);
            });
        } else {
            // Apply separate filters as AND conditions (Standard catalog behavior)
            if (!empty($levelFilter) && $levelFilter !== 'Semua Kursus') {
                $dbLevels = $levelMap[$levelFilter] ?? [$levelFilter];
                $query->whereIn('level', $dbLevels);
            }

            if (!empty($categoryFilter) && $categoryFilter !== 'semua' && $categoryFilter !== 'Semua Kursus') {
                $cat = strtolower($categoryFilter);
                $targetCategory = Category::where('slug', $cat)->orWhere('name', 'like', "%{$cat}%")->first();
                $categoryIds = [];
                if ($targetCategory) {
                    $categoryIds[] = $targetCategory->id;
                    $subcatIds = Category::where('parent_id', $targetCategory->id)->pluck('id')->toArray();
                    $categoryIds = array_merge($categoryIds, $subcatIds);
                }

                $query->where(function($q) use ($cat, $categoryIds) {
                    if (!empty($categoryIds)) {
                        $q->whereIn('category_id', $categoryIds);
                    } else {
                        $q->whereHas('category', function($cq) use ($cat) {
                            $cq->where('slug', $cat)->orWhere('name', 'like', "%{$cat}%");
                        });
                    }
                    
                    if (in_array($cat, ['sd', 'smp', 'sma', 'umum', 'workshop'])) {
                        $innerLevelMap = [
                            'sd' => ['SD', 'Kelas SD'],
                            'smp' => ['SMP', 'Kelas SMP'],
                            'sma' => ['SMA', 'Kelas SMA'],
                            'umum' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
                            'workshop' => ['Workshop', 'Kelas Workshop', 'workshop'],
                        ];
                        if (isset($innerLevelMap[$cat])) {
                            $q->orWhereIn('level', $innerLevelMap[$cat]);
                        }
                    }
                });
            }
        }

        // Filter by Search Query
        if ($request->has('search') && !empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = (int) (\App\Models\Setting::getValue('courses_per_page') ?: 12);
        
        $cacheKey = 'catalog_courses_' . md5(json_encode([
            'level' => $request->get('level'),
            'search' => $request->get('search'),
            'category' => $request->get('category'),
            'type' => $request->get('type'),
            'page' => $request->get('page', 1),
            'per_page' => $perPage,
        ]));

        $courses = \Illuminate\Support\Facades\Cache::remember($cacheKey, 3600, function () use ($query, $perPage) {
            return $query->latest()->paginate($perPage)->withQueryString();
        });

        $categories = \Illuminate\Support\Facades\Cache::remember('catalog_categories', 3600, function () {
            return Category::all();
        });

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'filters' => $request->only(['level', 'search', 'category', 'type']),
            'categories' => $categories
        ]);
    }

    /**
     * AJAX API endpoint for live dynamic course search & filtering
     */
    public function apiSearch(Request $request)
    {
        $query = Course::where('status', 'published')->select('id', 'title', 'slug', 'thumbnail', 'price', 'status', 'instructor_id', 'category_id', 'course_type', 'level', 'created_at')->with(['category:id,name,slug', 'instructor:id,name,photo', 'lessons']);

        // Filter by Course Type (async / live_class)
        if ($request->has('type') && !empty($request->type) && $request->type !== 'Semua Mode' && $request->type !== 'all') {
            if ($request->type === 'live_class' || $request->type === 'Kelas Kursus / Live Class') {
                $query->where('course_type', 'live_class');
            } elseif ($request->type === 'async' || $request->type === 'Kursus Mandiri') {
                $query->where(function($q) {
                    $q->where('course_type', '!=', 'live_class')->orWhereNull('course_type');
                });
            } else {
                $query->where('course_type', $request->type);
            }
        }

        $levelMap = [
            'Kelas SD' => ['SD', 'Kelas SD'],
            'Kelas SMP' => ['SMP', 'Kelas SMP'],
            'Kelas SMA' => ['SMA', 'Kelas SMA'],
            'Umum / Profesional' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
            'Kelas Umum' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
            'SD' => ['SD', 'Kelas SD'],
            'SMP' => ['SMP', 'Kelas SMP'],
            'SMA' => ['SMA', 'Kelas SMA'],
            'Umum' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
            'Workshop' => ['Workshop', 'Kelas Workshop', 'workshop'],
            'workshop' => ['Workshop', 'Kelas Workshop', 'workshop'],
        ];

        $levelFilter = $request->get('level');
        $categoryFilter = $request->get('category');

        $isSameConcept = false;
        if (!empty($levelFilter) && !empty($categoryFilter) && $levelFilter !== 'Semua Kursus' && $categoryFilter !== 'semua' && $categoryFilter !== 'Semua Kursus') {
            $lvl = strtolower($levelFilter);
            $cat = strtolower($categoryFilter);
            if (($cat === 'sd' && str_contains($lvl, 'sd')) ||
                ($cat === 'smp' && str_contains($lvl, 'smp')) ||
                ($cat === 'sma' && str_contains($lvl, 'sma')) ||
                ($cat === 'umum' && str_contains($lvl, 'umum')) ||
                ($cat === 'workshop' && str_contains($lvl, 'workshop'))) {
                $isSameConcept = true;
            }
        }

        if ($isSameConcept) {
            // Apply combined OR query: Match category (including subcategories) OR level keyword
            $query->where(function($q) use ($categoryFilter, $levelFilter, $levelMap) {
                $cat = strtolower($categoryFilter);
                $targetCategory = Category::where('slug', $cat)->orWhere('name', 'like', "%{$cat}%")->first();
                $categoryIds = [];
                if ($targetCategory) {
                    $categoryIds[] = $targetCategory->id;
                    $subcatIds = Category::where('parent_id', $targetCategory->id)->pluck('id')->toArray();
                    $categoryIds = array_merge($categoryIds, $subcatIds);
                }

                $q->where(function($subQ) use ($categoryIds, $cat) {
                    if (!empty($categoryIds)) {
                        $subQ->whereIn('category_id', $categoryIds);
                    } else {
                        $subQ->whereHas('category', function($cq) use ($cat) {
                            $cq->where('slug', $cat)->orWhere('name', 'like', "%{$cat}%");
                        });
                    }
                });

                $dbLevels = $levelMap[$levelFilter] ?? [$levelFilter];
                $q->orWhereIn('level', $dbLevels);
            });
        } else {
            // Apply separate filters as AND conditions (Standard catalog behavior)
            if (!empty($levelFilter) && $levelFilter !== 'Semua Kursus') {
                $dbLevels = $levelMap[$levelFilter] ?? [$levelFilter];
                $query->whereIn('level', $dbLevels);
            }

            if (!empty($categoryFilter) && $categoryFilter !== 'semua' && $categoryFilter !== 'Semua Kursus') {
                $cat = strtolower($categoryFilter);
                $targetCategory = Category::where('slug', $cat)->orWhere('name', 'like', "%{$cat}%")->first();
                $categoryIds = [];
                if ($targetCategory) {
                    $categoryIds[] = $targetCategory->id;
                    $subcatIds = Category::where('parent_id', $targetCategory->id)->pluck('id')->toArray();
                    $categoryIds = array_merge($categoryIds, $subcatIds);
                }

                $query->where(function($q) use ($cat, $categoryIds) {
                    if (!empty($categoryIds)) {
                        $q->whereIn('category_id', $categoryIds);
                    } else {
                        $q->whereHas('category', function($cq) use ($cat) {
                            $cq->where('slug', $cat)->orWhere('name', 'like', "%{$cat}%");
                        });
                    }
                    
                    if (in_array($cat, ['sd', 'smp', 'sma', 'umum', 'workshop'])) {
                        $innerLevelMap = [
                            'sd' => ['SD', 'Kelas SD'],
                            'smp' => ['SMP', 'Kelas SMP'],
                            'sma' => ['SMA', 'Kelas SMA'],
                            'umum' => ['Umum', 'Kelas Umum', 'Umum / Profesional'],
                            'workshop' => ['Workshop', 'Kelas Workshop', 'workshop'],
                        ];
                        if (isset($innerLevelMap[$cat])) {
                            $q->orWhereIn('level', $innerLevelMap[$cat]);
                        }
                    }
                });
            }
        }

        // Filter by Search Query
        if ($request->has('search') && !empty($request->search)) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = (int) (\App\Models\Setting::getValue('courses_per_page') ?: 12);
        $courses = $query->latest()->paginate($perPage)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $courses->items(),
            'pagination' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'total' => $courses->total(),
                'per_page' => $courses->perPage(),
                'links' => $courses->linkCollection()->toArray()
            ]
        ]);
    }

    /**
     * Display the course details page
     */
    public function show(string $slug)
    {
        // Check Course Visibility Setting
        $visibility = \App\Models\Setting::getValue('course_visibility');
        if (filter_var($visibility, FILTER_VALIDATE_BOOLEAN) && !auth()->check()) {
            return redirect()->to('/?login=true')->with('error', 'Please log in to view course details.');
        }

        $query = Course::where(function ($q) use ($slug) {
            $q->where('slug', $slug);
            if (is_numeric($slug)) {
                $q->orWhere('id', (int) $slug);
            }
        });

        // Allow instructors and admins (or course author) to preview draft/pending courses
        $user = auth()->user();
        $isStaffOrAuthor = $user && ($user->isAdmin() || $user->isInstructor());

        if (!$isStaffOrAuthor && !request()->has('preview')) {
            $query->where('status', 'published');
        }

        $course = $query->with([
                'category',
                'tags',
                'instructor',
                'modules.lessons' => function ($query) {
                    $query->select('id', 'module_id', 'title', 'duration_minutes', 'sort_order');
                },
                'modules.quizzes.questions',
                'reviews.user',
                'assessments'
            ])
            ->firstOrFail();

        // Check if current user is enrolled and wishlisted
        $isEnrolled = false;
        $isWishlisted = false;
        if (auth()->check()) {
            $isEnrolled = auth()->user()->hasEnrolled($course->id);
            $isWishlisted = \App\Models\Wishlist::where('user_id', auth()->id())
                ->where('course_id', $course->id)
                ->exists();
        }

        $contentSummary = filter_var(
            \App\Models\Setting::getValue('content_summary'),
            FILTER_VALIDATE_BOOLEAN
        );

        // Hide correct answer key index for all quiz questions in Inertia props
        $course->modules->each(function ($module) {
            $module->quizzes->each(function ($quiz) {
                $quiz->questions->each(function ($question) {
                    $question->makeHidden(['correct_option_index']);
                });
            });
        });

        $courseData = $course->toArray();
        $courseData['is_wishlisted'] = $isWishlisted;

        return Inertia::render('Courses/Show', [
            'course' => $courseData,
            'isEnrolled' => $isEnrolled,
            'showContentSummary' => $contentSummary
        ]);
    }

    /**
     * Display the course classroom learn page
     */
    public function learn(string $slug)
    {
        // 1. Authenticate check
        if (!auth()->check()) {
            return redirect()->to('/?login=true');
        }

        $user = auth()->user();

        // 2. Fetch course with published modules & lessons
        $query = Course::where(function ($q) use ($slug) {
            $q->where('slug', $slug);
            if (is_numeric($slug)) {
                $q->orWhere('id', (int) $slug);
            }
        });

        if (!$user->isAdmin() && !$user->isInstructor()) {
            $query->where('status', 'published');
        }

        $course = $query->with(['category', 'tags', 'instructor', 'modules.lessons', 'modules.quizzes.questions', 'modules.assessments', 'assessments'])
            ->firstOrFail();

        $enforcePrerequisites = filter_var(
            \App\Models\Setting::getValue('test_builder_enforce_prerequisites', 'true'),
            FILTER_VALIDATE_BOOLEAN
        );
        $previousModulePassed = true; // First module has no prerequisite restriction

        // Pre-fetch completed and passed assessment maps in 2 single batch queries (prevents N+1 in module loop)
        $completedMap = $user->getCompletedModuleAssessmentMap($course->id);
        $passedMap = $user->getPassedModuleAssessmentMap($course->id);

        $course->modules->each(function ($module) use ($user, &$previousModulePassed, $enforcePrerequisites, $completedMap, $passedMap) {
            $module->lessons->each(function ($lesson) {
                $lesson->makeHidden(['content', 'video_url', 'slide_url', 'slide_content']);
            });
            // Hide correct answer key index for all quiz questions in Inertia props
            $module->quizzes->each(function ($quiz) {
                $quiz->questions->each(function ($question) {
                    $question->makeHidden(['correct_option_index']);
                });
            });

            // Calculate assessment status per module using O(1) array map lookup
            $preTest = $module->assessments->where('type', 'pre_test')->first();
            $postTest = $module->assessments->where('type', 'post_test')->first();

            $isPreCompleted = (!$enforcePrerequisites || $module->enable_assessment === false || !$preTest) 
                ? true 
                : isset($completedMap["{$module->id}_pre_test"]);

            $isPostCompleted = (!$enforcePrerequisites || $module->enable_assessment === false || !$postTest) 
                ? true 
                : isset($passedMap["{$module->id}_post_test"]);

            $module->is_pre_completed = $isPreCompleted;
            $module->is_post_completed = $isPostCompleted;
            $module->is_prerequisite_met = !$enforcePrerequisites || $previousModulePassed || $user->isAdmin() || $user->id === $module->course->instructor_id;

            // Next module prerequisite requires this module's post-test passed (if post-test exists and enabled)
            $previousModulePassed = $isPostCompleted;
        });

        // 3. Authorization check (is Enrolled or is Instructor of course or is Admin)
        $isAuthor = $course->instructor_id === $user->id;
        
        $allowAccessWithoutEnroll = filter_var(
            \App\Models\Setting::getValue('course_content_access'),
            FILTER_VALIDATE_BOOLEAN
        );

        $isAuthorized = $user->hasEnrolled($course->id) || 
            $isAuthor || 
            ($allowAccessWithoutEnroll && ($user->isAdmin() || $user->isInstructor())) ||
            (!$allowAccessWithoutEnroll && $user->isAdmin());

        if (!$isAuthorized) {
            return redirect()->route('courses.show', $course->slug)
                ->with('warning', 'Silakan daftar di kelas ini terlebih dahulu untuk memulai belajar.');
        }

        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $completedLessons = $enrollment ? ($enrollment->completed_lessons ?? []) : [];
        $completedQuizzes = $enrollment ? ($enrollment->completed_quizzes ?? []) : [];
        $completedAt = $enrollment ? $enrollment->completed_at : null;

        $spotlightMode = filter_var(
            \App\Models\Setting::getValue('spotlight_mode'),
            FILTER_VALIDATE_BOOLEAN
        );

        // Generate or retrieve session-bound decryption key
        $decryptionKey = session('lesson_decryption_key');
        if (!$decryptionKey) {
            $decryptionKey = bin2hex(random_bytes(32)); // 64 hex characters (256-bit key)
            session(['lesson_decryption_key' => $decryptionKey]);
        }

        return Inertia::render('Courses/Learn', [
            'course' => $course,
            'spotlightMode' => $spotlightMode,
            'dbCompletedLessons' => $completedLessons,
            'dbCompletedQuizzes' => $completedQuizzes,
            'dbCompletedAt' => $completedAt,
            'decryptionKey' => $decryptionKey,
            'canJoinLive' => $user->can('joinLiveSession', $course),
        ]);
    }

    /**
     * Toggle completion of a lesson for the current user.
     */
    public function toggleLessonComplete(string $slug, string $lessonId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = auth()->user();

        // Get the user's enrollment
        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $completedLessons = $enrollment->completed_lessons ?? [];
        
        $lessonIdInt = (int) $lessonId;
        if (in_array($lessonIdInt, $completedLessons)) {
            // Remove lesson ID
            $completedLessons = array_values(array_filter($completedLessons, function($id) use ($lessonIdInt) {
                return $id !== $lessonIdInt;
            }));
        } else {
            // Add lesson ID
            $completedLessons[] = $lessonIdInt;

            // Log completion in student_learning_logs
            \App\Models\StudentLearningLog::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'lesson_id' => $lessonIdInt,
                'activity_type' => 'lesson_complete',
                'watch_seconds' => 0,
                'topic_name' => $course->category->name ?? $course->title
            ]);
        }

        $enrollment->completed_lessons = $completedLessons;

        // Check if all lessons AND quizzes of the course are completed
        $totalLessonsCount = $course->lessons()->count();
        $totalQuizzesCount = \App\Models\Quiz::whereIn('module_id', $course->modules()->pluck('id'))->count();
        $completedQuizzes = $enrollment->completed_quizzes ?? [];

        if (count($completedLessons) >= $totalLessonsCount && count($completedQuizzes) >= $totalQuizzesCount) {
            $enrollment->completed_at = now();
        } else {
            $enrollment->completed_at = null;
        }

        $enrollment->save();

        return response()->json([
            'completedLessons' => $completedLessons,
            'completedAt' => $enrollment->completed_at,
        ]);
    }

    /**
     * Mark a quiz as passed/completed for the current user.
     */
    public function toggleQuizComplete(Request $request, string $slug, string $quizId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = auth()->user();

        // Get the user's enrollment
        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->firstOrFail();

        $quizIdInt = (int) $quizId;
        $quiz = \App\Models\Quiz::with('questions')->findOrFail($quizIdInt);

        // Grade the quiz server-side
        $submittedAnswers = $request->input('answers', []);
        $correctCount = 0;
        $results = [];
        $questions = $quiz->questions;

        foreach ($questions as $q) {
            $studentAnswer = $submittedAnswers[$q->id] ?? '';
            $isCorrect = false;
            $type = 'multiple_choice';
            $correctText = '';
            
            $opts = $q->options ?? [];
            if (isset($opts[0]) && $opts[0] === '[TRUE_FALSE]') {
                $type = 'true_false';
                $correctIdx = (int) $q->correct_option_index;
                $correctText = $opts[$correctIdx + 1] ?? ($correctIdx === 0 ? 'Benar' : 'Salah');
                $isCorrect = (int) $studentAnswer === $correctIdx;
            } elseif (isset($opts[0]) && $opts[0] === '[ESSAY]') {
                $type = 'essay';
                $expectedKeyword = trim(strtolower($opts[1] ?? ''));
                $correctText = $opts[1] ?? '';
                $textAns = trim(strtolower($studentAnswer));
                $isCorrect = strpos($textAns, $expectedKeyword) !== false && strlen($expectedKeyword) > 0;
            } elseif (isset($opts[0]) && $opts[0] === '[MATH_FORMULA]') {
                $type = 'math_formula';
                $expectedFormula = trim(strtolower($opts[1] ?? ''));
                $expectedValue = trim(strtolower($opts[2] ?? ''));
                $correctText = 'Kunci: ' . ($opts[2] ?? $opts[1] ?? '');
                $textAns = trim(strtolower($studentAnswer));
                $isCorrect = $textAns === $expectedValue || $textAns === $expectedFormula;
            } else {
                $type = 'multiple_choice';
                $correctIdx = (int) $q->correct_option_index;
                $correctText = $opts[$correctIdx] ?? '';
                $isCorrect = (int) $studentAnswer === $correctIdx;
            }

            if ($isCorrect) {
                $correctCount++;
            }

            $results[] = [
                'question_text' => $q->question_text,
                'is_correct' => $isCorrect,
                'student_answer' => $studentAnswer,
                'correct_text' => $correctText,
                'type' => $type
            ];
        }

        $totalQuestions = count($questions);
        $score = $totalQuestions > 0 ? (int) round(($correctCount / $totalQuestions) * 100) : 0;

        $completedQuizzes = $enrollment->completed_quizzes ?? [];
        if ($score >= 70) {
            if (!in_array($quizIdInt, $completedQuizzes)) {
                $completedQuizzes[] = $quizIdInt;
            }
            $enrollment->completed_quizzes = $completedQuizzes;

            // Check if all lessons AND quizzes are completed
            $totalLessonsCount = $course->lessons()->count();
            $totalQuizzesCount = \App\Models\Quiz::whereIn('module_id', $course->modules()->pluck('id'))->count();
            $completedLessons = $enrollment->completed_lessons ?? [];

            if (count($completedLessons) >= $totalLessonsCount && count($completedQuizzes) >= $totalQuizzesCount) {
                $enrollment->completed_at = now();
            } else {
                $enrollment->completed_at = null;
            }

            $enrollment->save();
        }

        return response()->json([
            'score' => $score,
            'results' => $results,
            'completedQuizzes' => $completedQuizzes,
            'completedAt' => $enrollment->completed_at,
        ]);
    }

    /**
     * Log student learning progress (video play duration or lesson viewing)
     */
    public function logProgress(Request $request, string $slug, string $lessonId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $course = Course::where('slug', $slug)->firstOrFail();
        $user = auth()->user();
        
        $request->validate([
            'watch_seconds' => 'required|integer',
            'activity_type' => 'required|string'
        ]);

        $lesson = \App\Models\Lesson::findOrFail($lessonId);

        // Store log
        $log = \App\Models\StudentLearningLog::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            'lesson_id' => $lesson->id,
            'activity_type' => $request->activity_type,
            'watch_seconds' => $request->watch_seconds,
            'topic_name' => $course->category->name ?? $course->title
        ]);

        return response()->json(['success' => true]);
    }


    /**
     * View/Print course certificate
     */
    /**
     * View/Print course certificate
     */
    public function certificate(string $slug)
    {
        if (!auth()->check()) {
            return redirect()->to('/?login=true');
        }

        $user = auth()->user();
        $query = Course::where(function ($q) use ($slug) {
            $q->where('slug', $slug);
            if (is_numeric($slug)) {
                $q->orWhere('id', (int) $slug);
            }
        });

        if (!$user->isAdmin() && !$user->isInstructor()) {
            $query->where('status', 'published');
        }

        $course = $query->with(['instructor'])->firstOrFail();
        $isAuthor = $course->instructor_id === $user->id;

        // Check if enrolled and completed
        $enrollment = \App\Models\Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        $totalLessonsCount = $course->lessons()->count();
        $totalQuizzesCount = \App\Models\Quiz::whereIn('module_id', $course->modules()->pluck('id'))->count();

        // Safe fallback: completed if explicitly marked or completed lessons + quizzes are fully completed
        $isCompleted = ($enrollment && $enrollment->completed_at !== null) || 
                       ($enrollment && 
                        count($enrollment->completed_lessons ?? []) >= $totalLessonsCount && 
                        count($enrollment->completed_quizzes ?? []) >= $totalQuizzesCount);

        // Admins and instructors can always preview
        $canAccess = $user->isAdmin() || $isAuthor || $isCompleted;

        if (!$canAccess) {
            return redirect()->route('courses.show', $course->slug)
                ->with('warning', 'Sertifikat belum tersedia. Silakan selesaikan seluruh materi pembelajaran terlebih dahulu.');
        }

        // Get certificate specific settings keys
        $settings = [
            'cert_authorised_name' => \App\Models\Setting::getValue('cert_authorised_name') ?: 'John Doe',
            'cert_company_name' => \App\Models\Setting::getValue('cert_company_name') ?: 'Drastha Learning Inc.',
            'cert_page' => \App\Models\Setting::getValue('cert_page') ?: 'certificate',
            'cert_signature' => \App\Models\Setting::getValue('cert_signature') ?: '/images/signature-placeholder.png',
            'cert_show_instructor' => filter_var(\App\Models\Setting::getValue('cert_show_instructor') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        ];

        return Inertia::render('Courses/Certificate', [
            'course' => $course,
            'settings' => $settings,
            'completedAt' => $enrollment ? $enrollment->completed_at : now()->toDateTimeString(),
            'studentName' => $user->name,
        ]);
    }

    /**
     * Get encrypted lesson content dynamically
     */
    public function getLessonContent(string $slug, string $lessonId)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = auth()->user();
        $query = Course::where(function ($q) use ($slug) {
            $q->where('slug', $slug);
            if (is_numeric($slug)) {
                $q->orWhere('id', (int) $slug);
            }
        });

        if (!$user->isAdmin() && !$user->isInstructor()) {
            $query->where('status', 'published');
        }

        $course = $query->firstOrFail();
        $lesson = \App\Models\Lesson::where('id', $lessonId)
            ->whereIn('module_id', $course->modules()->pluck('id'))
            ->firstOrFail();

        $isAuthor = $course->instructor_id === $user->id;
        
        $allowAccessWithoutEnroll = filter_var(
            \App\Models\Setting::getValue('course_content_access'),
            FILTER_VALIDATE_BOOLEAN
        );

        $isAuthorized = $user->hasEnrolled($course->id) || 
            $isAuthor || 
            ($allowAccessWithoutEnroll && ($user->isAdmin() || $user->isInstructor())) ||
            (!$allowAccessWithoutEnroll && $user->isAdmin());

        if (!$isAuthorized) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $decryptionKey = session('lesson_decryption_key');
        if (!$decryptionKey) {
            return response()->json(['error' => 'Decryption session expired. Please reload page.'], 400);
        }

        $data = [
            'video_url' => $lesson->video_url,
            'slide_url' => $lesson->slide_url,
            'content' => $lesson->content,
            'slide_content' => $lesson->slide_content,
        ];

        $jsonData = json_encode($data);
        $method = 'aes-256-cbc';
        $key = hash('sha256', $decryptionKey, true);
        $ivLength = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $ciphertext = openssl_encrypt($jsonData, $method, $key, OPENSSL_RAW_DATA, $iv);

        return response()->json([
            'ciphertext' => base64_encode($ciphertext),
            'iv' => bin2hex($iv)
        ]);
    }
}
