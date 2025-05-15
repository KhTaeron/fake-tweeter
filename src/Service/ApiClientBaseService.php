<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class ApiClientBaseService
{
    protected string $apiBaseUrl;
    protected ?string $jwtToken = null;

    public function __construct(protected HttpClientInterface $client, string $apiBaseUrl)
    {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/') . '/api';
    }

    public function setJwtToken(string $token): void
    {
        $this->jwtToken = $token;
    }

    protected function fetchJson(string $endpoint): ?array
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $response = $this->client->request('GET', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                ],
            ]);

            return $response->toArray();
        } catch (\Throwable $e) {
            error_log('fetchJson error: ' . $e->getMessage());
            return null;
        }
    }

    protected function putJson(string $endpoint, array $data): bool
    {
        if (!$this->jwtToken) {
            throw new \LogicException('JWT token is not set. Please login first.');
        }

        try {
            $response = $this->client->request('PUT', $this->apiBaseUrl . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->jwtToken,
                    'Content-Type' => 'application/json',
                ],
                'json' => $data,
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Throwable $e) {
            error_log('putJson error: ' . $e->getMessage());
            return false;
        }
    }
}
