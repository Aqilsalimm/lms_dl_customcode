<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    protected $fillable = [
        'user_id', 
        'course_id', 
        'bundle_id', 
        'status',
        'enrolled_at',
        'expires_at',
        'completed_lessons',
        'completed_quizzes',
        'completed_at'
    ];

    protected $casts = [
        'enrolled_at' => 'datetime',
        'expires_at' => 'datetime',
        'completed_lessons' => 'array',
        'completed_quizzes' => 'array',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    protected static function booted()
    {
        static::creating(function ($enrollment) {
            if (empty($enrollment->enrolled_at)) {
                $enrollment->enrolled_at = now();
            }
            if (empty($enrollment->status)) {
                $enrollment->status = 'active';
            }
            if ($enrollment->course_id) {
                $course = $enrollment->course ?: Course::find($enrollment->course_id);
                if ($course && $course->access_duration_months) {
                    $enrollment->expires_at = $enrollment->enrolled_at->copy()->addMonths($course->access_duration_months);
                }
            }
        });

        static::created(function ($enrollment) {
            $user = $enrollment->user;
            $course = $enrollment->course;
            if ($user && $course) {
                $user->notify(new \App\Notifications\LmsNotification(
                    'course_enrolled',
                    'student',
                    'Course Enrolled Successfully',
                    "You have successfully enrolled in the course: {$course->title}",
                    "/courses/{$course->slug}"
                ));
            }
        });

        static::deleted(function ($enrollment) {
            $user = $enrollment->user;
            $course = $enrollment->course;
            if ($user && $course) {
                // Send Cancel Enrollment Notification
                $user->notify(new \App\Notifications\LmsNotification(
                    'cancel_enrollment',
                    'student',
                    'Enrollment Cancelled',
                    "Your enrollment in the course \"{$course->title}\" has been cancelled.",
                    "/courses"
                ));

                // Send Removed From Course Notification
                $user->notify(new \App\Notifications\LmsNotification(
                    'removed_from_course',
                    'student',
                    'Removed From Course',
                    "You have been removed from the course: {$course->title}",
                    "/courses"
                ));
            }
        });
    }
}
