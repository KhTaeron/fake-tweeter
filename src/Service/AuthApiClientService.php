<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AuthApiClientService extends ApiClientBaseService
{
    public function login(string $username, string $password, SessionInterface $session): bool
    {
        try {
            $response = $this->client->request('POST', $this->apiBaseUrl . '/login', [
                'json' => [
                    'pseudo' => $username,
                    'password' => $password,
                ]
            ]);

            $data = $response->toArray();
            $this->jwtToken = $data['token'] ?? null;

            if ($this->jwtToken) {
                $session->set('jwt_token', $this->jwtToken);
                return true;
            }

            return false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
