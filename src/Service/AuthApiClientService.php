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

    public function register(array $data): array
    {
        try {
            $response = $this->client->request('POST', $this->apiBaseUrl . '/register', [
                'json' => $data,
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getContent(false);

            return [
                'success' => in_array($statusCode, [200, 201]),
                'status' => $statusCode,
                'data' => json_decode($body, true),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'status' => 500,
                'data' => ['error' => $e->getMessage()],
            ];
        }
    }

}
