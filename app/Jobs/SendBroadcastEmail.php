<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendBroadcastEmail implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public User $user,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
    ) {
    }

    public function handle(): void
    {
        if (!$this->user->email) {
            return;
        }

        Mail::send('emails.broadcast', [
            'title' => $this->title,
            'body' => $this->body,
            'actionUrl' => $this->actionUrl,
            'userName' => $this->user->name,
        ], function ($message) {
            $message->to($this->user->email, $this->user->name)
                ->subject($this->title);
        });
    }
}
