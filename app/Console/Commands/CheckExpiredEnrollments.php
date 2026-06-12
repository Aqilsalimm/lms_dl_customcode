<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\Enrollment;
use App\Models\Course;
use App\Models\User;

#[Signature('app:check-expired-enrollments')]
#[Description('Scan and mark expired course enrollments, sending notifications to students.')]
class CheckExpiredEnrollments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredEnrollments = Enrollment::where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with(['user', 'course'])
            ->get();

        if ($expiredEnrollments->isEmpty()) {
            $this->info('No expired enrollments found.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($expiredEnrollments as $enrollment) {
            $enrollment->update(['status' => 'expired']);
            $count++;

            $user = $enrollment->user;
            $course = $enrollment->course;

            if ($user && $course) {
                try {
                    $user->notify(new \App\Notifications\LmsNotification(
                        'enrollment_expired',
                        'student',
                        'Masa Akses Kelas Berakhir',
                        "Akses belajar Anda untuk kelas \"{$course->title}\" telah berakhir karena masa lisensi telah habis.",
                        "/courses/{$course->slug}"
                    ));
                    $this->info("Marked enrollment #{$enrollment->id} as expired and notified student {$user->email}");
                } catch (\Exception $e) {
                    $this->error("Error sending notification for enrollment #{$enrollment->id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Successfully processed {$count} expired enrollments.");
        return Command::SUCCESS;
    }
}
