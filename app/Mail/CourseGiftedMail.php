<?php

namespace App\Mail;

use App\Models\Course;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CourseGiftedMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $student;
    public Course $course;
    public string $giftedBy;

    /**
     * Create a new message instance.
     */
    public function __construct(User $student, Course $course, string $giftedBy)
    {
        $this->student = $student;
        $this->course = $course;
        $this->giftedBy = $giftedBy;
    }

    /**
     * Get the message envelope.
     */
    public function getEnvelope(): Envelope
    {
        return new Envelope(
            subject: '[Drastha Learning] Anda Telah Menerima Akses Kelas: ' . $this->course->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function getContent(): Content
    {
        return new Content(
            view: 'emails.course-gifted',
            with: [
                'studentName' => $this->student->name,
                'courseTitle' => $this->course->title,
                'giftedBy' => $this->giftedBy,
                'courseUrl' => url('/courses/' . $this->course->slug),
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
