<?php
namespace Almani\Xero\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
class XeroTokenExpired extends Notification
{
    use Queueable;
    public function via($notifiable){ return ['mail']; }
    public function toMail($notifiable){
        return (new MailMessage)->subject('Xero Access Token Expired')
            ->line('Your Xero access token has expired. Please re-authorize Xero integration.')
            ->action('Reconnect Xero', url('/xero/redirect'));
    }
}