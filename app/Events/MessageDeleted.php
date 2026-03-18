<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public int $messageId;
    public int $conversationId;

    public function __construct(Message $message)
    {
        $this->messageId = $message->id;
        $this->conversationId = $message->conversation_id;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->conversationId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->messageId,
        ];
    }
}
