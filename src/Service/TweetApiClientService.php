<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TweetApiClientService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiBaseUrl
    ) {
    }

    public function getTweets(string $keyword = ''): array
    {
        if (!empty($keyword)) {
            $url = $this->apiBaseUrl . '/tweets/search?q=' . urlencode($keyword);
        } else {
            $url = $this->apiBaseUrl . '/tweets';
        }

        return $this->fetchJson($url) ?? [];
    }

    
    public function getTweet(int $id): ?array
    {
        return $this->fetchJson($this->apiBaseUrl . "/tweets/$id");
    }

    public function getLikes(int $id): array
    {
        return $this->fetchJson($this->apiBaseUrl . "/tweets/$id/likes");
    }
    private function fetchJson(string $url): ?array
    {
        try {
            $response = $this->client->request('GET', $url);
            
            return $response->toArray();
        } catch (\Throwable $e) {
            error_log('fetchJson error: ' . $e->getMessage());
            return null;
        }
    }
}
