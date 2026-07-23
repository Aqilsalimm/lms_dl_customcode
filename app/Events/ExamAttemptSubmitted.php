<?php

namespace App\Events;

use App\Models\WorkshopAssessmentAttempt;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExamAttemptSubmitted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $attemptId;
    public $studentName;
    public $courseTitle;
    public $assessmentType;
    public $score;
    public $isPassed;
    public $completedAt;

    /**
     * Create a new event instance.
     */
    public function __construct(WorkshopAssessmentAttempt $attempt)
    {
        $attempt->loadMissing(['user', 'assessment.course']);

        $this->attemptId = $attempt->id;
        $this->studentName = $attempt->user->name ?? 'Student';
        $this->courseTitle = $attempt->assessment->course->title ?? 'Course';
        $this->assessmentType = $attempt->assessment->type ?? 'test';
        $this->score = (float) $attempt->total_score;
        $this->isPassed = (bool) $attempt->is_passed;
        $this->completedAt = $attempt->completed_at ? $attempt->completed_at->toIso8601String() : now()->toIso8601String();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('exam-reports'),
        ];
    }

    /**
     * Event name for broadcasting.
     */
    public function broadcastAs(): string
    {
        return 'ExamAttemptSubmitted';
    }
}
