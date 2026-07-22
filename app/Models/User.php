<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use NotificationChannels\WebPush\HasPushSubscriptions;

#[Fillable(['name', 'email', 'password', 'role', 'status', 'photo', 'google_id', 'google_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasPushSubscriptions;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Role checks
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    // Relationships
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function instructorProfile(): HasOne
    {
        return $this->hasOne(InstructorProfile::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function paymentProfile(): HasOne
    {
        return $this->hasOne(InstructorPaymentProfile::class);
    }

    /**
     * Ebooks purchased by the user.
     */
    public function purchasedEbooks(): BelongsToMany
    {
        return $this->belongsToMany(Ebook::class, 'user_ebooks', 'user_id', 'ebook_id')
                    ->withTimestamps();
    }

    /**
     * Ebooks published/authored by this user.
     */
    public function publishedEbooks(): HasMany
    {
        return $this->hasMany(Ebook::class, 'user_id');
    }

    /**
     * Transactions made by the user for ebooks.
     */
    public function ebookTransactions(): HasMany
    {
        return $this->hasMany(EbookTransaction::class, 'user_id');
    }

    // Check enrollment helper
    public function hasEnrolled(int $courseId): bool
    {
        $enrollment = $this->enrollments()->where('course_id', $courseId)->first();
        if (!$enrollment) {
            return false;
        }

        if ($enrollment->status === 'expired') {
            return false;
        }

        if ($enrollment->expires_at && $enrollment->expires_at->isPast()) {
            $enrollment->update(['status' => 'expired']);
            return false;
        }

        return true;
    }

    public function assessmentAttempts(): HasMany
    {
        return $this->hasMany(WorkshopAssessmentAttempt::class, 'user_id');
    }

    public function hasPassedAssessment($courseId, $assessmentType)
    {
        // Menggunakan whereHas untuk mengecek relasi secara efisien
        return $this->assessmentAttempts()->whereHas('assessment', function ($query) use ($courseId, $assessmentType) {
            $query->where('course_id', $courseId)
                  ->where('type', $assessmentType);
        })
        ->where('is_passed', true) // Hanya mencari attempt yang statusnya lulus
        ->exists(); // Mengembalikan boolean (true/false)
    }

    public function hasCompletedModuleAssessment($moduleId, $assessmentType): bool
    {
        return $this->assessmentAttempts()->whereHas('assessment', function ($query) use ($moduleId, $assessmentType) {
            $query->where('module_id', $moduleId)
                  ->where('type', $assessmentType);
        })
        ->where('status', 'completed')
        ->exists();
    }

    public function hasPassedModuleAssessment($moduleId, $assessmentType): bool
    {
        return $this->assessmentAttempts()->whereHas('assessment', function ($query) use ($moduleId, $assessmentType) {
            $query->where('module_id', $moduleId)
                  ->where('type', $assessmentType);
        })
        ->where('is_passed', true)
        ->exists();
    }
}
