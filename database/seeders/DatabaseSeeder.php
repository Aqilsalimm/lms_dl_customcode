<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Check if running in production environment
        if (app()->environment('production') || config('app.env') === 'production') {
            $this->call(ProductionSeeder::class);
            return;
        }

        // 0. Seed License Key for Tests/Development
        \App\Models\Setting::updateOrCreate(
            ['key' => 'license_key'],
            ['value' => 'DRSTHA-DEVELOPER-BYPASS-9999']
        );
        // 1. Seed Users (Admin, Instructor, Student)
        $admin = User::updateOrCreate([
            'email' => 'admin@drastha.com'
        ], [
            'name' => 'Admin Drastha',
            'role' => 'admin',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $devAdmin = User::updateOrCreate([
            'email' => 'dev-admin@drasthabest.com'
        ], [
            'name' => 'Dev Admin',
            'role' => 'admin',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $instructor = User::updateOrCreate([
            'email' => 'instructor@drastha.com'
        ], [
            'name' => 'Instructor Drastha',
            'role' => 'instructor',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $student = User::updateOrCreate([
            'email' => 'student@drastha.com'
        ], [
            'name' => 'Student Drastha',
            'role' => 'student',
            'password' => 'password',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // 2. Seed Categories
        $categories = [
            ['name' => 'IT & Software', 'slug' => 'it-software', 'description' => 'Kelas pemrograman, web dev, Python, dll.'],
            ['name' => 'Finance & Accounting', 'slug' => 'finance-accounting', 'description' => 'Kelas akuntansi, finansial, dan audit.'],
            ['name' => 'Sains & Matematika', 'slug' => 'sains-matematika', 'description' => 'Kelas untuk SD, SMP, SMA.'],
            ['name' => 'Umum', 'slug' => 'umum', 'description' => 'Seminar, sertifikasi umum, dll.']
        ];

        $categoryModels = [];
        foreach ($categories as $cat) {
            $categoryModels[] = \App\Models\Category::firstOrCreate(['slug' => $cat['slug']], $cat);
        }

        // 3. Seed Tags
        $tags = ['Python', 'HTML', 'CSS', 'Programming', 'Audit', 'Sains'];
        $tagModels = [];
        foreach ($tags as $tag) {
            $tagModels[] = \App\Models\Tag::firstOrCreate(['name' => $tag], [
                'name' => $tag,
                'slug' => \Illuminate\Support\Str::slug($tag)
            ]);
        }

        $courseData = [
            [
                'title' => 'Python Class : Pemrograman dan Perkenalan Bahasa Python',
                'bg_color' => '#FF4D4F',
                'icon_type' => 'code',
                'price' => 500000.00,
                'level' => 'Umum',
                'capacity' => 25,
                'category_id' => $categoryModels[0]->id,
                'status' => 'published',
                'description' => 'Kelas dasar Python untuk pemula yang ingin memahami konsep pemrograman dan data science.',
                'course_type' => 'async',
            ],
            [
                'title' => 'Website Class : Pemrograman Website dengan HTML dan CSS',
                'bg_color' => '#44A6D9',
                'icon_type' => 'code',
                'price' => 450000.00,
                'level' => 'SMA',
                'capacity' => 20,
                'category_id' => $categoryModels[0]->id,
                'status' => 'published',
                'description' => 'Belajar cara membuat website interaktif dari nol menggunakan HTML5 dan CSS3.',
                'course_type' => 'async',
            ],
            [
                'title' => 'Audit Class : Pengenalan Audit Forensik Dasar untuk Pemula',
                'bg_color' => '#73D13D',
                'icon_type' => 'calculator',
                'price' => 600000.00,
                'level' => 'Umum',
                'capacity' => 15,
                'category_id' => $categoryModels[1]->id,
                'status' => 'published',
                'description' => 'Kursus singkat mengenalkan metodologi investigasi keuangan dan deteksi kecurangan.',
                'course_type' => 'live_class',
                'delivery_mode' => 'offline',
                'start_date' => now()->addDays(2),
                'end_date' => now()->addDays(3),
                'location_venue' => 'Gedung Utama Drastha Learning, Jakarta',
                'meeting_url' => 'https://zoom.us/j/1234567890'
            ]
        ];

        foreach ($courseData as $c) {
            $course = \App\Models\Course::firstOrCreate([
                'title' => $c['title']
            ], array_merge($c, [
                'instructor_id' => $instructor->id,
                'slug' => \Illuminate\Support\Str::slug($c['title']),
                'about' => 'Di kelas ini Anda akan dipandu oleh instruktur berpengalaman secara tatap muka (offline) maupun online, dengan kurikulum terstruktur dan tugas evaluasi berkala.'
            ]));

            if (isset($c['course_type']) && $c['course_type'] === 'live_class') {
                \App\Models\LiveClass::firstOrCreate([
                    'course_id' => $course->id,
                    'title' => 'Sesi Utama Live Class: ' . $course->title
                ], [
                    'mode' => 'hybrid',
                    'meeting_link' => 'https://zoom.us/j/1234567890',
                    'venue_name' => 'Gedung Utama Drastha Learning',
                    'venue_address' => 'Jl. Sudirman No. 1, Jakarta',
                    'gmaps_url' => 'https://goo.gl/maps/1234567890',
                    'offline_capacity' => 10,
                    'start_time' => now()->addDays(2),
                    'end_time' => now()->addDays(2)->addHours(2),
                    'is_published' => true,
                ]);
            }

            // Sync Tags
            $course->tags()->sync([$tagModels[0]->id, $tagModels[3]->id]);

            // Add modules & lessons
            $module = \App\Models\Module::firstOrCreate([
                'course_id' => $course->id,
                'title' => 'Dasar Teori dan Pengenalan'
            ], [
                'sort_order' => 0
            ]);

            \App\Models\Lesson::firstOrCreate([
                'module_id' => $module->id,
                'title' => 'Sesi 1: Perkenalan Lingkungan Belajar & Tools'
            ], [
                'content' => 'Dalam sesi awal ini, kita akan membahas program belajar, instalasi tools/IDE, dan mempersiapkan workspace kita.',
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'duration_minutes' => 45,
                'sort_order' => 0
            ]);

            \App\Models\Lesson::firstOrCreate([
                'module_id' => $module->id,
                'title' => 'Sesi 2: Menulis Baris Kode Pertama'
            ], [
                'content' => 'Di sesi kedua ini, kita akan langsung mempraktikkan penulisan sintaks pertama kita secara runut dan menjelaskan outputnya.',
                'duration_minutes' => 60,
                'sort_order' => 1
            ]);

            // Add Quiz
            $quiz = \App\Models\Quiz::firstOrCreate([
                'module_id' => $module->id,
                'title' => 'Kuis Evaluasi Bab 1'
            ], [
                'description' => 'Kerjakan kuis ini untuk menguji pemahaman teori dasar yang telah dipelajari di Bab 1.',
                'time_limit_minutes' => 15,
                'sort_order' => 0
            ]);

            \App\Models\QuizQuestion::firstOrCreate([
                'quiz_id' => $quiz->id,
                'question_text' => 'Manakah yang merupakan penulisan variabel yang valid?'
            ], [
                'options' => ['my-variable = 10', 'my_variable = 10', '10variable = 10', 'my variable = 10'],
                'correct_option_index' => 1,
                'sort_order' => 0
            ]);

            // Add Pre-Test
            $pretest = \App\Models\WorkshopAssessment::firstOrCreate([
                'course_id' => $course->id,
                'module_id' => $module->id,
                'type' => 'pre_test'
            ], [
                'title' => 'Pre-Test Evaluasi Awal',
                'description' => 'Kerjakan Pre-Test ini sebelum memulai materi.',
                'duration_minutes' => 10,
                'passing_score' => 0,
                'max_attempts' => 0,
                'is_published' => true
            ]);

            \App\Models\WorkshopAssessmentQuestion::firstOrCreate([
                'assessment_id' => $pretest->id,
                'question_text' => 'Pilih opsi pertama'
            ], [
                'options' => ['Opsi A', 'Opsi B'],
                'correct_answer' => '0',
                'points' => 50,
                'order_number' => 0
            ]);

            \App\Models\WorkshopAssessmentQuestion::firstOrCreate([
                'assessment_id' => $pretest->id,
                'question_text' => 'Pilih opsi kedua'
            ], [
                'options' => ['Opsi A', 'Opsi B'],
                'correct_answer' => '1',
                'points' => 50,
                'order_number' => 1
            ]);
        }

        // 5. Seed some mock orders and enrollments for rich dashboard data
        $course1 = \App\Models\Course::first();
        if ($course1) {
            // Student purchases python class
            $order = \App\Models\Order::firstOrCreate([
                'user_id' => $student->id,
                'buyable_id' => $course1->id,
                'buyable_type' => \App\Models\Course::class,
            ], [
                'amount' => $course1->price,
                'status' => 'completed',
                'transaction_id' => 'TRX-' . uniqid(),
                'payment_type' => 'bank_transfer'
            ]);

            \App\Models\Enrollment::firstOrCreate([
                'user_id' => $student->id,
                'course_id' => $course1->id
            ], [
                'enrolled_at' => now()
            ]);
        }

        // 6. Seed 12 identical visual blog posts matching mockup grid exactly
        for ($i = 1; $i <= 12; $i++) {
            \App\Models\Blog::firstOrCreate([
                'slug' => 'belajar-php-mysql-dengan-asik-dan-menyenangkan-' . $i
            ], [
                'user_id' => $admin->id,
                'title' => 'Belajar PHP, MySQL dengan Asik dan Menyenangkan.',
                'excerpt' => 'Discover Insights. Fuel Your Curiosity. Dive into a world of insightful articles, expert opinions, and inspiring stories.',
                'content' => 'Dalam artikel ini kita akan mengupas tuntas cara belajar PHP & MySQL yang interaktif, asik, mudah dipahami, serta langsung mempraktikkannya untuk membuat web dinamis.',
                'category' => 'Coding IT Class',
                'image' => null,
                'status' => 'published',
                'created_at' => now()->subDays(12 - $i)
            ]);
        }
    }
}
