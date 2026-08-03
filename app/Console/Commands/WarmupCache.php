<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Course;
use App\Models\LiveClass;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WarmupCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warmup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up application cache (settings, configs) for high concurrency events';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cache warmup...');
        Log::info('Cache warmup started.');

        // 1. Warmup Settings
        if (class_exists(Setting::class)) {
            $this->info('Warming up Setting cache...');
            $settings = Setting::all();
            foreach ($settings as $setting) {
                // Call getValue to trigger Cache::remember inside the model
                Setting::getValue($setting->key);
            }
            // Optional: Warm up the pluck cache if it's used somewhere
            Cache::remember('all_settings_pluck', 3600, function () {
                return Setting::pluck('value', 'key')->toArray();
            });
            $this->info('Settings cached successfully. (' . $settings->count() . ' items)');
        }

        // 2. Warmup Categories (digunakan di navigasi & filter homepage)
        if (class_exists(Category::class)) {
            $this->info('Warming up Category cache...');
            $categories = Category::all();
            Cache::remember('all_categories', 3600, function () use ($categories) {
                return $categories->toArray();
            });
            $this->info('Categories cached. (' . $categories->count() . ' items)');
        }

        // 3. Warmup Tags (homepage tags cloud)
        if (class_exists(Tag::class)) {
            $this->info('Warming up Tag cache...');
            $tags = Tag::all();
            Cache::remember('all_tags', 3600, function () use ($tags) {
                return $tags->toArray();
            });
            $this->info('Tags cached. (' . $tags->count() . ' items)');
        }

        // 4. Warmup Course durations & sessions (accessor cache di Course model)
        //    Dipakai di kartu course listing & halaman detail.
        if (class_exists(Course::class)) {
            $this->info('Warming up Course metadata cache...');
            $courses = Course::where('status', 'published')
                ->orWhere('status', 'active')
                ->limit(100)
                ->get();
            foreach ($courses as $course) {
                // Trigger accessor duration & sessions (Cache::remember inside model)
                $course->duration;
                $course->sessions;
            }
            $this->info('Courses cached. (' . $courses->count() . ' items)');
        }

        // 5. Warmup upcoming LiveClass (untuk event offline besar)
        if (class_exists(LiveClass::class)) {
            $this->info('Warming up LiveClass cache...');
            $upcoming = LiveClass::where('is_published', true)
                ->where('start_time', '>=', now())
                ->orderBy('start_time')
                ->limit(50)
                ->get();
            Cache::remember('upcoming_live_classes', 1800, function () use ($upcoming) {
                return $upcoming->toArray();
            });
            $this->info('LiveClasses cached. (' . $upcoming->count() . ' items)');
        }

        $this->info('Cache warmup completed successfully.');
        Log::info('Cache warmup completed.');
    }
}
