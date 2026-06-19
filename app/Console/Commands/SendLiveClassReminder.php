<?php

namespace App\Console\Commands;

use App\Models\Course;
use App\Mail\LiveClassReminderMail;
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
    protected $description = 'Send email reminders to instructors 24 hours before their scheduled live class sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting SendLiveClassReminder command...');

        // Find live classes starting in the next 24 hours
        $courses = Course::with('instructor')
            ->where('course_type', 'live_class')
            ->whereNotNull('start_date')
            ->whereBetween('start_date', [now(), now()->addHours(24)])
            ->get();

        if ($courses->isEmpty()) {
            $this->info('No live classes scheduled in the next 24 hours.');
            return 0;
        }

        $sentCount = 0;

        foreach ($courses as $course) {
            $about = [];
            if ($course->about && str_starts_with($course->about, '{') && str_ends_with($course->about, '}')) {
                try {
                    $about = json_decode($course->about, true) ?: [];
                } catch (\Exception $e) {}
            }

            // Check if reminder was already sent
            if (!empty($about['live_class_reminder_sent'])) {
                $this->info("Reminder already sent for course: {$course->title}");
                continue;
            }

            $instructor = $course->instructor;
            if (!$instructor || !$instructor->email) {
                $this->warn("No instructor email found for course: {$course->title}");
                continue;
            }

            try {
                // Send the mailable
                Mail::to($instructor->email)->send(new LiveClassReminderMail($course));

                // Mark as sent
                $about['live_class_reminder_sent'] = true;
                $course->update([
                    'about' => json_encode($about)
                ]);

                $this->info("Successfully sent reminder for course: {$course->title} to {$instructor->email}");
                Log::info("Live class reminder sent for course: {$course->title} (ID: {$course->id}) to {$instructor->email}");
                $sentCount++;
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for course: {$course->title}. Error: " . $e->getMessage());
                Log::error("Failed to send live class reminder for course: {$course->title} (ID: {$course->id}). Error: " . $e->getMessage());
            }
        }

        $this->info("Completed. Sent {$sentCount} reminders.");
        return 0;
    }
}
