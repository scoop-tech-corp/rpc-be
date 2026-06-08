<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $type;
    public $userId;

    public function __construct($message, $type, $userId)
    {
        $this->message = $message;
        $this->type    = $type;
        $this->userId  = $userId;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('notification.' . $this->userId);
    }

    public function broadcastAs()
    {
        return 'message-popup';
    }
}
