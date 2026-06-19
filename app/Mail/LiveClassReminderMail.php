<?php

namespace App\Mail;

use App\Models\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class LiveClassReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Course $course;

    /**
     * Create a new message instance.
     */
    public function __construct(Course $course)
    {
        $this->course = $course;
    }

    /**
     * Get the message envelope.
     */
    public function getEnvelope(): Envelope
    {
        return new Envelope(
            subject: '[Drastha Learning] Pengingat Kelas Live: ' . $this->course->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function getContent(): Content
    {
        Carbon::setLocale('id');
        $startDateFormatted = $this->course->start_date 
            ? Carbon::parse($this->course->start_date)->translatedFormat('l, d F Y H:i') 
            : 'Belum Diatur';

        $platform = 'Zoom';
        if ($this->course->meeting_url && str_contains($this->course->meeting_url, 'meet.google.com')) {
            $platform = 'Google Meet';
        }

        return new Content(
            view: 'emails.live-class-reminder',
            with: [
                'instructorName' => $this->course->instructor->name ?? 'Instruktur',
                'courseTitle' => $this->course->title,
                'startDate' => $startDateFormatted,
                'timezone' => $this->course->timezone ?: 'Asia/Jakarta',
                'platform' => $platform,
                'meetingUrl' => $this->course->meeting_url,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
