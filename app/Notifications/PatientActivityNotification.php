<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PatientActivityNotification extends Notification
{
    use Queueable;

    public string $title;
    public string $message;
    public string $category; // 'appointment' | 'treatment' | 'billing' | 'payment' | 'general'
    public ?string $actionUrl;
    public ?string $actionText;
    public array $details;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $title,
        string $message,
        string $category = 'general',
        ?string $actionUrl = null,
        ?string $actionText = null,
        array $details = []
    ) {
        $this->title = $title;
        $this->message = $message;
        $this->category = $category;
        $this->actionUrl = $actionUrl;
        $this->actionText = $actionText;
        $this->details = $details;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Delivers both to database and to registered email address
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("[Vitality Smile] {$this->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message);

        if (!empty($this->details)) {
            foreach ($this->details as $key => $value) {
                if ($value !== null && $value !== '') {
                    $mail->line("**{$key}:** {$value}");
                }
            }
        }

        if ($this->actionUrl && $this->actionText) {
            $mail->action($this->actionText, url($this->actionUrl));
        }

        $mail->line("If you have any questions or need to make changes, please contact Vitality Smile Dental Clinic during operating hours (Monday to Friday, 9:00 AM – 6:00 PM).")
            ->salutation("Best regards,\nVitality Smile Dental Clinic Team");

        return $mail;
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'category' => $this->category,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'details' => $this->details,
            'created_at' => now()->toIso8601String(),
        ];
    }
}

