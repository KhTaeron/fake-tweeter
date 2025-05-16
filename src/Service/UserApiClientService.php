<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserApiClientService extends ApiClientBaseService
{
    public function setTokenFromSession(SessionInterface $session): void
    {
        $token = $session->get('jwt_token');
        if ($token) {
            $this->setJwtToken($token);
        }
    }

    public function getUser(int $id): ?array
    {
        return $this->fetchJson("/users/$id");
    }

    public function getMe(): ?array
    {
        return $this->fetchJson("/users/me");
    }

    public function updateMe(array $data): bool
    {
        return $this->putJson('/users/me', $data);
    }

    public function deleteMe(): bool
    {
        return $this->delete('/users/me');
    }

    public function toggleSubscription(int $targetUserId): bool
    {
        return $this->postJson("/users/{$targetUserId}/follow", []);
    }

}
