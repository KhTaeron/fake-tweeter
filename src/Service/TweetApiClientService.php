<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TweetApiClientService extends ApiClientBaseService
{
    public function setTokenFromSession(SessionInterface $session): void
    {
        $token = $session->get('jwt_token');
        if ($token) {
            $this->setJwtToken($token);
        }
    }

    public function getTweets(string $keyword = ''): array
    {
        if (!empty($keyword)) {
            $url = '/tweets/search?q=' . urlencode($keyword);
            return $this->fetchJson($url);
        } else {
            return $this->fetchJson('/tweets');
        }
    }

    

    public function getTweet(int $id): ?array
    {
        return $this->fetchJson("/tweets/$id");
    }

    public function getLikes(int $id): array
    {
        return $this->fetchJson("/tweets/$id/likes");
    }

    public function likeTweet(int $tweetId): bool
    {
        return $this->postJson("/tweets/$tweetId/likes", []);
    }

    public function createTweet(array $data): bool
    {
        return $this->postJson('/tweets', $data);   // on envoie maintenant le contenu
    }


}
