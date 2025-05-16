<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class NotificationApiClientService extends ApiClientBaseService
{
    public function setTokenFromSession(SessionInterface $session): void
    {
        $token = $session->get('jwt_token');
        if ($token) {
            $this->setJwtToken($token);
        }
    }

    public function getNotifications(): array
    {
        return $this->fetchJson('/notifications');
    }

    public function markAsRead($notifId): bool
    {
        return $this->putJson("/notifications/{$notifId}/read", data:[]);
    }

}
