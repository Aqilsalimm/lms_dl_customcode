<?php

declare(strict_types=1);

namespace App\Services\Spreadsheet;

use App\Models\Course;
use App\Models\Module;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Setting;
use App\Models\User;
use Exception;

class CourseImportService
{
    /**
     * Process parsed spreadsheet rows to insert Course, Module, and Lesson models.
     *
     * @param array $rows
     * @param int|null $courseId
     * @param User|null $user
     * @return array
     */
    public function processImportRows(array $rows, ?int $courseId = null, ?User $user = null): array
    {
        $user = $user ?? auth()->user();
        $log = [];

        $header = array_map('strtolower', array_map('trim', $rows[0]));

        $idx_type = array_search('row type', $header) !== false ? array_search('row type', $header) : 0;
        $idx_title = array_search('title', $header) !== false ? array_search('title', $header) : 1;
        $idx_desc = array_search('description', $header) !== false ? array_search('description', $header) : 2;
        $idx_publish_status = array_search('publish status', $header) !== false ? array_search('publish status', $header) : 3;

        // Step 1: Basics
        $idx_course_type = array_search('course type', $header) !== false ? array_search('course type', $header) : 4;
        $idx_difficulty = array_search('difficulty level', $header) !== false ? array_search('difficulty level', $header) : 5;
        $idx_payment_type = array_search('payment type', $header) !== false ? array_search('payment type', $header) : 6;
        $idx_price_type = array_search('price type', $header) !== false ? array_search('price type', $header) : 7;
        $idx_price = array_search('price', $header) !== false ? array_search('price', $header) : 8;
        $idx_capacity = array_search('capacity', $header) !== false ? array_search('capacity', $header) : 10;

        // Step 1: Schedule
        $idx_start_date = array_search('start date', $header) !== false ? array_search('start date', $header) : 11;
        $idx_end_date = array_search('end date', $header) !== false ? array_search('end date', $header) : 12;
        if ($idx_start_date === false) $idx_start_date = array_search('start_date', $header) !== false ? array_search('start_date', $header) : 11;
        if ($idx_end_date === false) $idx_end_date = array_search('end_date', $header) !== false ? array_search('end_date', $header) : 12;
        $idx_timezone = array_search('timezone', $header) !== false ? array_search('timezone', $header) : 13;
        $idx_meeting_url = array_search('meeting url', $header) !== false ? array_search('meeting url', $header) : 14;

        // Step 1: Tools
        $idx_tools = array_search('tools', $header) !== false ? array_search('tools', $header) : 15;

        // Step 3: Additional / Benefits
        $idx_benefit_overview = array_search('benefit: overview', $header) !== false ? array_search('benefit: overview', $header) : 16;
        $idx_benefit_learn = array_search('benefit: what will learn', $header) !== false ? array_search('benefit: what will learn', $header) : 17;
        $idx_benefit_target = array_search('benefit: target audience', $header) !== false ? array_search('benefit: target audience', $header) : 18;
        $idx_benefit_req = array_search('benefit: requirements', $header) !== false ? array_search('benefit: requirements', $header) : 19;

        // Duration
        $idx_hours = array_search('duration hours', $header) !== false ? array_search('duration hours', $header) : 20;
        $idx_minutes = array_search('duration minutes', $header) !== false ? array_search('duration minutes', $header) : 21;

        $current_course_id = $courseId ?: 0;
        $current_module_id = 0;
        $module_order = 0;
        $lesson_order = 0;

        $log[] = '<span style="color:#10b981; font-weight:bold;">[START] Memulai pemrosesan baris data kelas...</span>';

        for ($i = 1; $i < count($rows); $i++) {
            $data = $rows[$i];
            if (empty($data) || !isset($data[$idx_type]) || empty(trim((string)$data[$idx_type]))) {
                continue;
            }

            $type = strtolower(trim((string)$data[$idx_type]));
            $title = isset($data[$idx_title]) ? trim((string)$data[$idx_title]) : '';
            $desc = isset($data[$idx_desc]) ? trim((string)$data[$idx_desc]) : '';

            $publish_status = isset($data[$idx_publish_status]) ? strtolower(trim((string)$data[$idx_publish_status])) : 'publish';
            $requested_status = ($publish_status === 'publish' || $publish_status === 'published') ? 'published' : 'draft';
            $status = $requested_status;

            // Moderation check for non-admin users
            if ($user && !$user->isAdmin() && $status === 'published') {
                $moderationEnabled = Setting::getValue('instructor_course_moderation');
                if (filter_var($moderationEnabled, FILTER_VALIDATE_BOOLEAN)) {
                    $status = 'pending';
                }
            }

            if (empty($title)) {
                $log[] = '<span style="color:#f59e0b;">[Baris ' . ($i + 1) . '] Lewati: Judul kosong.</span>';
                continue;
            }

            if ($type === 'course') {
                if ($courseId) {
                    $current_course_id = $courseId;
                    $log[] = '<span style="color:#38bdf8;">[COURSE APPEND] Menyisipkan materi ke kelas yang sedang aktif (ID: ' . $courseId . ')</span>';
                    continue;
                }

                $current_module_id = 0;
                $module_order = 0;
                $lesson_order = 0;

                $course_type = isset($data[$idx_course_type]) ? strtolower(trim((string)$data[$idx_course_type])) : 'async';
                if ($course_type !== 'live_class') $course_type = 'async';

                $level = isset($data[$idx_difficulty]) ? trim((string)$data[$idx_difficulty]) : 'Umum';

                $payment_input = isset($data[$idx_payment_type]) ? strtolower(trim((string)$data[$idx_payment_type])) : 'one-time';
                $payment_type = 'one-time';
                if ($payment_input === 'monthly' || $payment_input === 'langganan' || $payment_input === '/bulan') {
                    $payment_type = 'monthly';
                }

                $price_type = isset($data[$idx_price_type]) ? strtolower(trim((string)$data[$idx_price_type])) : 'free';
                $price = 0.00;
                if ($price_type === 'paid' && isset($data[$idx_price])) {
                    $price = (float) filter_var($data[$idx_price], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                }

                $capacity = isset($data[$idx_capacity]) ? (int)$data[$idx_capacity] : 20;

                $start_date = !empty($data[$idx_start_date]) ? trim((string)$data[$idx_start_date]) : null;
                $end_date = !empty($data[$idx_end_date]) ? trim((string)$data[$idx_end_date]) : null;
                $timezone = !empty($data[$idx_timezone]) ? trim((string)$data[$idx_timezone]) : 'Asia/Jakarta';
                $meeting_url = !empty($data[$idx_meeting_url]) ? trim((string)$data[$idx_meeting_url]) : null;

                $tools_str = isset($data[$idx_tools]) ? trim((string)$data[$idx_tools]) : '';
                $tools_array = !empty($tools_str) ? array_map('trim', explode(',', $tools_str)) : [];

                $about_obj = [
                    'class_type' => ($course_type === 'live_class' ? 'Online' : 'Offline'),
                    'overview' => isset($data[$idx_benefit_overview]) ? trim((string)$data[$idx_benefit_overview]) : '',
                    'what_will_learn' => isset($data[$idx_benefit_learn]) ? trim((string)$data[$idx_benefit_learn]) : '',
                    'target_audience' => isset($data[$idx_benefit_target]) ? trim((string)$data[$idx_benefit_target]) : '',
                    'duration_hours' => 0,
                    'materials_included' => 0,
                    'requirements' => isset($data[$idx_benefit_req]) ? trim((string)$data[$idx_benefit_req]) : '',
                    'selected_certificate' => 'template_1',
                    'custom_certificates' => [],
                    'prerequisites' => [],
                    'attachments' => [],
                    'live_zoom_link' => '',
                    'live_zoom_data' => null,
                    'live_gmeet_link' => '',
                    'live_gmeet_data' => null,
                    'intro_video_url' => ''
                ];

                try {
                    $course = Course::create([
                        'instructor_id' => $user ? $user->id : auth()->id(),
                        'course_type' => $course_type,
                        'title' => $title,
                        'description' => $desc,
                        'about' => json_encode($about_obj),
                        'bg_color' => '#44A6D9',
                        'icon_type' => 'code',
                        'price' => $price,
                        'payment_type' => $payment_type,
                        'level' => $level,
                        'capacity' => $capacity,
                        'status' => $status,
                        'start_date' => $start_date,
                        'end_date' => $end_date,
                        'timezone' => $timezone,
                        'meeting_url' => $meeting_url,
                        'tools' => $tools_array
                    ]);

                    $current_course_id = $course->id;
                    $log[] = '<span style="color:#38bdf8;">[COURSE CREATED] ID ' . $current_course_id . ' : ' . htmlspecialchars($title) . ' (' . strtoupper($course_type) . ' - ' . $status . ')</span>';
                } catch (Exception $e) {
                    $log[] = '<span style="color:#ef4444;">[COURSE FAILED] Gagal membuat Kursus "' . htmlspecialchars($title) . '": ' . $e->getMessage() . '</span>';
                }

            } elseif ($type === 'topic' || $type === 'module') {
                if ($current_course_id <= 0) {
                    $log[] = '<span style="color:#ef4444;">[MODULE FAILED] Gagal membuat Bab/Topic "' . htmlspecialchars($title) . '": Tidak ada Kursus aktif di baris sebelumnya.</span>';
                    continue;
                }

                $module_order++;
                $lesson_order = 0;

                try {
                    $module = Module::create([
                        'course_id' => $current_course_id,
                        'title' => $title,
                        'sort_order' => $module_order
                    ]);

                    $current_module_id = $module->id;
                    $log[] = '<span style="color:#a855f7;">  [MODULE CREATED] ID ' . $current_module_id . ' : ' . htmlspecialchars($title) . ' (Urutan: ' . $module_order . ')</span>';
                } catch (Exception $e) {
                    $log[] = '<span style="color:#ef4444;">  [MODULE FAILED] Gagal membuat Bab: ' . $e->getMessage() . '</span>';
                }

            } elseif ($type === 'lesson') {
                if ($current_course_id <= 0 || $current_module_id <= 0) {
                    $log[] = '<span style="color:#ef4444;">[LESSON FAILED] Gagal membuat Pelajaran "' . htmlspecialchars($title) . '": Harus berada di bawah Kursus dan Bab yang valid.</span>';
                    continue;
                }

                $lesson_order++;

                $hours = isset($data[$idx_hours]) ? (int)$data[$idx_hours] : 0;
                $mins = isset($data[$idx_minutes]) ? (int)$data[$idx_minutes] : 0;
                $duration = ($hours * 60) + $mins;
                if ($duration <= 0) $duration = 30;

                try {
                    Lesson::create([
                        'module_id' => $current_module_id,
                        'title' => $title,
                        'content' => json_encode([
                            'type' => 'text',
                            'summary' => $desc,
                            'featured_image' => null,
                            'exercise_files' => [],
                            'is_preview' => false
                        ]),
                        'video_url' => '',
                        'duration_minutes' => $duration,
                        'sort_order' => $lesson_order
                    ]);

                    $log[] = '<span style="color:#10b981;">    [LESSON CREATED] ' . htmlspecialchars($title) . ' (Durasi: ' . $duration . ' Menit, Urutan: ' . $lesson_order . ')</span>';
                } catch (Exception $e) {
                    $log[] = '<span style="color:#ef4444;">    [LESSON FAILED] Gagal membuat Pelajaran: ' . $e->getMessage() . '</span>';
                }
            } else {
                $log[] = '<span style="color:#f59e0b;">[UNKNOWN TYPE] Lewati tipe baris tidak dikenal: "' . htmlspecialchars($type) . '"</span>';
            }
        }

        $log[] = '<span style="color:#10b981; font-weight:bold;">[FINISH] Selesai memproses berkas template! Semua data kelas berhasil disimpan ke sistem.</span>';
        return $log;
    }

    /**
     * Process parsed spreadsheet rows to insert Quiz and QuizQuestion models.
     *
     * @param array $rows
     * @param string $quiz_title
     * @param int|string $module_id
     * @return array
     */
    public function processQuizImportRows(array $rows, string $quiz_title, $module_id): array
    {
        $log = [];
        $header = array_map('strtolower', array_map('trim', $rows[0]));

        $idx_type = array_search('question type', $header) !== false ? array_search('question type', $header) : 0;
        $idx_text = array_search('question text', $header) !== false ? array_search('question text', $header) : 1;
        $idx_points = array_search('points / marks', $header) !== false ? array_search('points / marks', $header) : 2;
        $idx_choice_a = array_search('choice a', $header) !== false ? array_search('choice a', $header) : 3;
        $idx_choice_b = array_search('choice b', $header) !== false ? array_search('choice b', $header) : 4;
        $idx_choice_c = array_search('choice c', $header) !== false ? array_search('choice c', $header) : 5;
        $idx_choice_d = array_search('choice d', $header) !== false ? array_search('choice d', $header) : 6;
        $idx_choice_e = array_search('choice e', $header) !== false ? array_search('choice e', $header) : 7;
        $idx_correct = array_search('correct answer key', $header) !== false ? array_search('correct answer key', $header) : 8;

        $log[] = '<span style="color:#10b981; font-weight:bold;">[START] Memulai pemrosesan kuis...</span>';

        $module = Module::find($module_id);
        if (!$module) {
            $log[] = '<span style="color:#ef4444; font-weight:bold;">[ERROR] Bab/Module target tidak ditemukan.</span>';
            return $log;
        }

        try {
            $quiz = Quiz::create([
                'module_id' => $module_id,
                'title' => $quiz_title,
                'description' => 'Quiz imported from spreadsheet template.',
                'time_limit_minutes' => 30,
                'sort_order' => ($module->quizzes()->count() + 1)
            ]);

            $log[] = '<span style="color:#38bdf8; font-weight:bold;">[QUIZ CREATED] ID: ' . $quiz->id . ' - "' . htmlspecialchars($quiz_title) . '" berhasil ditautkan ke Bab "' . htmlspecialchars($module->title) . '".</span>';
        } catch (Exception $e) {
            $log[] = '<span style="color:#ef4444; font-weight:bold;">[QUIZ FAILED] Gagal membuat kuis di database: ' . $e->getMessage() . '</span>';
            return $log;
        }

        $question_count = 0;

        for ($i = 1; $i < count($rows); $i++) {
            $data = $rows[$i];
            if (empty($data) || !isset($data[$idx_text]) || empty(trim((string)$data[$idx_text]))) {
                continue;
            }

            $q_type = isset($data[$idx_type]) ? strtolower(trim((string)$data[$idx_type])) : 'single_choice';
            $q_text = trim((string)$data[$idx_text]);
            $correct_key_raw = isset($data[$idx_correct]) ? strtoupper(trim((string)$data[$idx_correct])) : '';

            $options = [];
            if ($q_type === 'true_false' || $q_type === 'true/false') {
                $options = ['True', 'False'];
            } else {
                if (!empty($data[$idx_choice_a])) $options[] = trim((string)$data[$idx_choice_a]);
                if (!empty($data[$idx_choice_b])) $options[] = trim((string)$data[$idx_choice_b]);
                if (!empty($data[$idx_choice_c])) $options[] = trim((string)$data[$idx_choice_c]);
                if (!empty($data[$idx_choice_d])) $options[] = trim((string)$data[$idx_choice_d]);
                if (!empty($data[$idx_choice_e])) $options[] = trim((string)$data[$idx_choice_e]);
            }

            if (empty($options)) {
                $log[] = '<span style="color:#f59e0b;">  [SOAL LEWATI] Baris ' . ($i + 1) . ': Opsi jawaban kosong.</span>';
                continue;
            }

            $correct_index = 0;
            if ($q_type === 'true_false' || $q_type === 'true/false') {
                if ($correct_key_raw === 'B' || strtolower($correct_key_raw) === 'false') {
                    $correct_index = 1;
                } else {
                    $correct_index = 0;
                }
            } else {
                $first_key = 'A';
                if (!empty($correct_key_raw)) {
                    $split_keys = preg_split('/[\s,|]+/', $correct_key_raw);
                    if (!empty($split_keys[0])) {
                        $first_key = strtoupper(trim($split_keys[0]));
                    }
                }

                $map = ['A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4];
                $correct_index = isset($map[$first_key]) ? $map[$first_key] : 0;

                if ($correct_index >= count($options)) {
                    $correct_index = 0;
                }
            }

            try {
                QuizQuestion::create([
                    'quiz_id' => $quiz->id,
                    'question_text' => $q_text,
                    'options' => $options,
                    'correct_option_index' => $correct_index,
                    'sort_order' => ($question_count + 1)
                ]);

                $log[] = '<span style="color:#a855f7;">  [SOAL BERHASIL] ID: ' . ($question_count + 1) . ' | Tipe: ' . $q_type . ' | "' . htmlspecialchars($q_text) . '"</span>';
                $log[] = '    -> Opsi: [' . implode(', ', array_map(function($opt, $idx) use ($correct_index) {
                    return $idx === $correct_index ? '<span style="color:#10b981; font-weight:bold;">' . htmlspecialchars((string)$opt) . '</span>' : htmlspecialchars((string)$opt);
                }, $options, array_keys($options))) . ']';

                $question_count++;
            } catch (Exception $e) {
                $log[] = '<span style="color:#ef4444;">  [SOAL GAGAL] Gagal menyuntikkan soal baris ' . ($i + 1) . ': ' . $e->getMessage() . '</span>';
            }
        }

        $log[] = '<span style="color:#10b981; font-weight:bold;">[FINISH] Selesai menyuntikkan ' . $question_count . ' soal kuis ke database!</span>';
        return $log;
    }
}
