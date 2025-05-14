<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class TweetApiClientService
{
    private string $apiBaseUrl;

    public function __construct(
        private HttpClientInterface $client,
        string $apiBaseUrl // injecté via services.yaml ou .env
    ) {
        $this->apiBaseUrl = rtrim($apiBaseUrl, '/');
    }

    public function getTweets(string $keyword = ''): array
    {
        $url = $this->apiBaseUrl . '/tweets';
        if ($keyword !== '') {
            $url .= '?q=' . urlencode($keyword);
        }

        return $this->fetchJson($url);
    }

    public function getTweet(int $id): array
    {
        return $this->fetchJson($this->apiBaseUrl . '/tweets/' . $id);
    }

    public function getLikes(int $tweetId): array
    {
        return $this->fetchJson($this->apiBaseUrl . '/tweets/' . $tweetId . '/likes');
    }

    private function fetchJson(string $url): array
    {
        try {
            $response = $this->client->request('GET', $url);
            if ($response->getStatusCode() >= 400) {
                return [];
            }
            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            // Logguer ici si nécessaire
            return [];
        }
    }
}
