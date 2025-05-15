<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserApiClientService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiBaseUrl
    ) {}

    public function getUser(int $id): ?array
    {
        return $this->fetchJson($this->apiBaseUrl . "/users/$id");
    }

    public function getMe(): ?array
    {
        return $this->fetchJson($this->apiBaseUrl . "/users/me");
    }

    private function fetchJson(string $url): ?array
    {
        try {
            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() !== 200) {
                return null;
            }
            return $response->toArray();
        } catch (\Throwable) {
            return null;
        }
    }
}
