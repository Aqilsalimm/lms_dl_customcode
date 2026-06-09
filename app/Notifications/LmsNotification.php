<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Setting;

class LmsNotification extends Notification
{
    use Queueable;

    public $eventType;
    public $recipientRole;
    public $title;
    public $message;
    public $actionUrl;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $eventType, string $recipientRole, string $title, string $message, ?string $actionUrl = null)
    {
        $this->eventType = $eventType;
        $this->recipientRole = $recipientRole;
        $this->title = $title;
        $this->message = $message;
        $this->actionUrl = $actionUrl;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        // Retrieve settings based on role
        $settingKey = 'notification_' . $this->recipientRole;
        $settingJson = Setting::where('key', $settingKey)->value('value');
        
        if ($settingJson) {
            $settings = json_decode($settingJson, true);
            $eventConfig = $settings[$this->eventType] ?? null;

            if ($eventConfig) {
                if (!empty($eventConfig['onsite'])) {
                    $channels[] = 'database';
                }
                if (!empty($eventConfig['push'])) {
                    // Simulate push notification dispatch by logging it
                    \Log::info("PUSH NOTIFICATION SIMULATION [Role: {$this->recipientRole}, Event: {$this->eventType}] Sent to User ID {$notifiable->id} ({$notifiable->email}): Title: \"{$this->title}\", Message: \"{$this->message}\", Action URL: \"{$this->actionUrl}\"");
                }
            }
        } else {
            // Default fallbacks if settings aren't stored yet
            $channels[] = 'database';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_type' => $this->eventType,
            'recipient_role' => $this->recipientRole,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
        ];
    }
}
