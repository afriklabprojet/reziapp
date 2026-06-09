<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Message;
use App\Models\NotificationLog;
use App\Models\User;

interface NotificationInterface
{
    public function sendMessageNotification(Message $message, User $recipient): void;

    public function sendPaymentNotification(User $recipient, array $data): void;

    public function sendSecurityNotification(User $recipient, string $title, string $body, array $data = []): void;

    public function sendInAppNotification(User $recipient, string $type, string $title, string $body, array $data = []): NotificationLog;

    public function getUnreadCount(User $user): int;

    public function markAllAsRead(User $user): int;
}
