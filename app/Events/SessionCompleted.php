<?php

namespace App\Events;

use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public TrainingSession $session;
    public User $completedBy;

    /**
     * Create a new event instance.
     *
     * @param TrainingSession $session The session that was marked as completed
     * @param User $completedBy The admin/trainer who marked the session as completed
     */
    public function __construct(TrainingSession $session, User $completedBy)
    {
        $this->session = $session;
        $this->completedBy = $completedBy;
    }
}
