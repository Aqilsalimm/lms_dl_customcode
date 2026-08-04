<?php

namespace App\Jobs;

use App\Mail\UserInvitationMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendUserInvitationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public array $backoff = [60, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $role,
        public readonly string $setupUrl,
    ) {
        $this->onQueue('emails');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->email)->send(new UserInvitationMail(
            $this->name,
            $this->email,
            $this->role,
            $this->setupUrl,
        ));
    }
}
