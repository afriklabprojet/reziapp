<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailFr extends VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage())
            ->subject('Vérification de votre adresse e-mail')
            ->greeting('Bonjour !')
            ->line('Veuillez cliquer sur le bouton ci-dessous pour vérifier votre adresse e-mail.')
            ->action('Vérifier mon adresse e-mail', $url)
            ->line('Si vous n\'avez pas créé de compte, aucune action n\'est requise.')
            ->salutation('Cordialement, l\'équipe ReziApp');
    }
}
