<?php

namespace App\Notifications;

use App\Models\StudyGroupJoinRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestNotification extends Notification
{
    use Queueable;
    protected $joinRequest;
    /**
     * Create a new notification instance.
     */
    public function __construct(StudyGroupJoinRequest $joinRequest)
    {
        $this->joinRequest = $joinRequest;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => $this->joinRequest->user->first_name . ' requested to join ' . $this->joinRequest->group->group_name,
            'group_id' => $this->joinRequest->group_id,
            'user_id' => $this->joinRequest->user_id,
            'user_name' => $this->joinRequest->user->first_name . ' '  . $this->joinRequest->user->last_name,       
            'status' => $this->joinRequest->status,
        ];
    }
}
