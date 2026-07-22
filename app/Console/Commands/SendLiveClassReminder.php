<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Mail\LiveClassReminderMail;
use App\Notifications\LiveClassReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendLiveClassReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'liveclass:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch queued email & web push reminders to enrolled students and instructors before live class sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SendLiveClassReminder command...');

        $this->handleInstructor24hReminders();
        $this->handleStudent1hReminders();

        $this->info('SendLiveClassReminder command completed successfully.');
        return 0;
    }

    /**
     * Send email reminders to instructors 24 hours before their live class.
     */
    protected function handleInstructor24hReminders()
    {
        $courses = Course::with('instructor')
            ->where('course_type', 'live_class')
            ->whereNotNull('start_date')
            ->whereBetween('start_date', [now(), now()->addHours(24)])
            ->get();

        foreach ($courses as $course) {
            $about = $this->parseAboutJson($course->about);

            if (!empty($about['live_class_reminder_sent'])) {
                continue;
            }

            $instructor = $course->instructor;
            if (!$instructor || !$instructor->email) {
                continue;
            }

            try {
                Mail::to($instructor->email)->send(new LiveClassReminderMail($course));

                $about['live_class_reminder_sent'] = true;
                $course->update(['about' => json_encode($about)]);

                $this->info("Instructor reminder sent for course: {$course->title} to {$instructor->email}");
                Log::info("Instructor live class reminder sent for course: {$course->title} (ID: {$course->id})");
            } catch (\Exception $e) {
                $this->error("Failed to send instructor reminder for course: {$course->title}. Error: " . $e->getMessage());
            }
        }
    }

    /**
     * Dispatch queued notification (Mail & WebPush) to students 1 hour before their live class.
     */
    protected function handleStudent1hReminders()
    {
        // Target live classes starting in the next 1 hour
        $courses = Course::with('enrollments.user')
            ->where('course_type', 'live_class')
            ->whereNotNull('start_date')
            ->whereBetween('start_date', [now(), now()->addHour()])
            ->get();

        foreach ($courses as $course) {
            $about = $this->parseAboutJson($course->about);

            if (!empty($about['live_class_student_reminder_sent'])) {
                continue;
            }

            $dispatchedCount = 0;

            foreach ($course->enrollments as $enrollment) {
                $user = $enrollment->user;
                if ($user) {
                    // Dispatch queued notification to student (Queue Worker will send email via Brevo & WebPush)
                    $user->notify(new LiveClassReminder($course));
                    $dispatchedCount++;
                }
            }

            // Also notify the instructor via the queued notification if desired
            if ($course->instructor) {
                $course->instructor->notify(new LiveClassReminder($course));
                $dispatchedCount++;
            }

            $about['live_class_student_reminder_sent'] = true;
            $course->update(['about' => json_encode($about)]);

            $this->info("Dispatched {$dispatchedCount} queued student reminders for course: {$course->title}");
            Log::info("Live class student reminders dispatched for course: {$course->title} (ID: {$course->id}) - Total: {$dispatchedCount}");
        }
    }

    /**
     * Helper to safely decode course about JSON attribute.
     */
    protected function parseAboutJson($aboutRaw): array
    {
        if (is_array($aboutRaw)) {
            return $aboutRaw;
        }

        if (is_string($aboutRaw) && str_starts_with(trim($aboutRaw), '{')) {
            try {
                return json_decode($aboutRaw, true) ?: [];
            } catch (\Exception $e) {}
        }

        return [];
    }
}
