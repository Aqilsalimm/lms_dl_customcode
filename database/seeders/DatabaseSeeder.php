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
        // 1. Seed Users (Admin, Instructor, Student)
        $admin = User::firstOrCreate([
            'email' => 'admin@drastha.com'
        ], [
            'name' => 'Admin Drastha',
            'role' => 'admin',
            'password' => bcrypt('password'),
        ]);

        $instructor = User::firstOrCreate([
            'email' => 'instructor@drastha.com'
        ], [
            'name' => 'Instructor Drastha',
            'role' => 'instructor',
            'password' => bcrypt('password'),
        ]);

        $student = User::firstOrCreate([
            'email' => 'student@drastha.com'
        ], [
            'name' => 'Student Drastha',
            'role' => 'student',
            'password' => bcrypt('password'),
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
                'description' => 'Kelas dasar Python untuk pemula yang ingin memahami konsep pemrograman dan data science.'
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
                'description' => 'Belajar cara membuat website interaktif dari nol menggunakan HTML5 dan CSS3.'
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
                'description' => 'Kursus singkat mengenalkan metodologi investigasi keuangan dan deteksi kecurangan.'
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
