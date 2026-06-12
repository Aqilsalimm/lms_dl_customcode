<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Course;
use App\Models\Bundle;
use App\Mail\AbandonedCartReminder as ReminderMail;
use Illuminate\Support\Facades\Mail;

#[Signature('app:send-abandoned-cart-reminders')]
#[Description('Scan pending orders and send recovery emails for abandoned checkouts.')]
class SendAbandonedCartReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $enabledSetting = Setting::where('key', 'abandoned_cart_reminder_enabled')->first();
        $enabled = $enabledSetting ? filter_var($enabledSetting->value, FILTER_VALIDATE_BOOLEAN) : false;

        if (!$enabled) {
            $this->info('Abandoned cart reminders are disabled in settings.');
            return Command::SUCCESS;
        }

        $delaySetting = Setting::where('key', 'abandoned_cart_reminder_delay')->value('value') ?: 60;
        $delay = (int) $delaySetting;

        $subject = Setting::where('key', 'abandoned_cart_email_subject')->value('value') 
            ?: 'Ayo selesaikan pembelian kelas Anda di Drastha Learning!';
        $bodyTemplate = Setting::where('key', 'abandoned_cart_email_body')->value('value') 
            ?: "Halo {student_name},\n\nKami melihat Anda meninggalkan kelas berikut di keranjang belanja Anda:\n{course_names}\n\nJangan biarkan semangat belajar Anda padam! Klik tautan berikut untuk melanjutkan checkout Anda:\n{checkout_link}\n\nSalam Hangat,\nTim Drastha Learning";

        $cutoffTime = now()->subMinutes($delay);
        $past24Hours = now()->subHours(24);

        $abandonedOrders = Order::where('status', 'pending')
            ->where('abandoned_reminder_sent', false)
            ->where('created_at', '<=', $cutoffTime)
            ->where('created_at', '>=', $past24Hours)
            ->with(['user'])
            ->get();

        if ($abandonedOrders->isEmpty()) {
            $this->info('No abandoned checkouts found to process.');
            return Command::SUCCESS;
        }

        $count = 0;

        foreach ($abandonedOrders as $order) {
            $user = $order->user;
            if (!$user) {
                continue;
            }

            // Determine item names
            $courseNames = '';
            if ($order->buyable_type === Course::class) {
                $buyable = Course::find($order->buyable_id);
                $courseNames = $buyable ? $buyable->title : 'Kelas Pilihan';
            } elseif ($order->buyable_type === Bundle::class) {
                $buyable = Bundle::with('courses')->find($order->buyable_id);
                if ($buyable) {
                    $courseNames = $buyable->courses->pluck('title')->implode(', ');
                } else {
                    $courseNames = 'Paket Kelas Pilihan';
                }
            }

            // Create recovery button
            $resumeUrl = route('checkout.resume', $order->id);
            $buttonHtml = '<a href="' . $resumeUrl . '" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #1A2B49 0%, #264790 100%); color: #ffffff; text-decoration: none; border-radius: 12px; font-weight: 700; margin-top: 15px; box-shadow: 0 4px 12px rgba(38,71,144,0.2);">' . __('Lanjutkan Belajar Sekarang') . '</a>';

            // Replace template parameters
            $replacements = [
                '{student_name}' => $user->name,
                '{course_names}' => $courseNames,
                '{checkout_link}' => $buttonHtml,
                '{discount_amount}' => 'Rp ' . number_format($order->discount_amount ?: 0, 0, ',', '.'),
            ];

            $emailBody = str_replace(array_keys($replacements), array_values($replacements), $bodyTemplate);

            try {
                Mail::to($user->email)->send(new ReminderMail($subject, $emailBody));
                
                $order->update(['abandoned_reminder_sent' => true]);
                $count++;
                $this->info("Reminder sent to {$user->email} for Order #{$order->id}");
            } catch (\Exception $e) {
                $this->error("Failed to send email to {$user->email}: " . $e->getMessage());
            }
        }

        $this->info("Successfully sent {$count} abandoned checkout reminders.");
        return Command::SUCCESS;
    }
}
