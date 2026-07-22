<?php

namespace App\Notifications;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushMessage;
use NotificationChannels\WebPush\WebPushChannel;

class LiveClassReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public Course $course;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 30;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int, int>
     */
    public $backoff = [10, 30, 60];

    /**
     * Get the middleware the job should pass through.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            new ThrottlesExceptions(10, 60),
        ];
    }

    /**
     * Create a new notification instance.
     */
    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        // Web push channel will be dynamically appended if user has push subscriptions
        if (method_exists($notifiable, 'routeNotificationForWebPush') && $notifiable->routeNotificationForWebPush($this)) {
            $channels[] = 'webpush';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $liveUrl = route('live-class.show', $this->course->id);
        $startTime = $this->course->start_date 
            ? date('H:i d M Y', strtotime($this->course->start_date)) 
            : 'Segera';

        return (new MailMessage)
            ->subject("⏰ Pengingat Sesi Live: {$this->course->title}")
            ->greeting("Halo {$notifiable->name},")
            ->line("Sesi Live Class untuk kursus **{$this->course->title}** akan segera dimulai pada **{$startTime}**.")
            ->line('Pastikan Anda telah menyelesaikan Pre-test (jika ada) agar dapat langsung memasuki ruang pertemuan online.')
            ->action('Gabung Sesi Live Class', $liveUrl)
            ->line('Terima kasih telah belajar bersama Drastha Learning!');
    }

    /**
     * Get the array representation of the notification for database/broadcast.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'course_id' => $this->course->id,
            'title' => $this->course->title,
            'start_date' => $this->course->start_date,
            'url' => route('live-class.show', $this->course->id),
        ];
    }

    /**
     * Get the web push representation of the notification.
     */
    public function toWebPush(object $notifiable, $notification): WebPushMessage
    {
        $liveUrl = route('live-class.show', $this->course->id);

        return (new WebPushMessage)
            ->title("⏰ Kelas {$this->course->title} Segera Dimulai!")
            ->icon('/images/logo/logo_dl.png')
            ->body("Sesi Live Class akan dimulai dalam 1 jam. Jangan lupa selesaikan Pre-test sebelum masuk ruangan.")
            ->action('Masuk Kelas', 'join_class')
            ->data(['url' => $liveUrl]);
    }
}
