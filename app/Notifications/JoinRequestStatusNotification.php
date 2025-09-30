<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class JoinRequestStatusNotification extends Notification
{
    use Queueable;

    public $status;
    public $joinRequest;
    public $groupName;
    /**
     * Create a new notification instance.
     */
    public function __construct($joinRequest, $status, $groupName)
    {
        $this->joinRequest = $joinRequest;
        $this->status = $status;
        $this->groupName = $groupName;
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
        $group = $this->joinRequest->group;
        $user = $this->joinRequest->user;
        $groupName = $this->groupName;

        $message = $this->status === 'approved' 
            ? "Your request to join the study group '{$groupName}' has been approved." 
            : "Your request to join the study group '{$groupName}' has been denied.";
        return [
            'message' => $message,
            'group_id' => $this->joinRequest->group_id,
            'status' => $this->status,
        ];
    }
}
