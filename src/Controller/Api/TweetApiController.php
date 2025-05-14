<?php

namespace App\Controller\Api;

use App\Service\TweetApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/home')]
class TweetApiController extends AbstractController
{
    #[Route('', name: 'tweets_home', methods: ['GET'])]
    public function home(Request $request, TweetApiClientService $api): Response
    {
        $keyword = $request->query->get('q', '');
        $tweets = $api->getTweets($keyword);

        return $this->render('tweet/home.html.twig', [
            'tweets' => $tweets,
            'keyword' => $keyword,
        ]);
    }

    #[Route('/tweet/{id}', name: 'tweet_detail', requirements: ['id' => '\d+'])]
    public function show(int $id, TweetApiClientService $api): Response
    {
        $tweet = $api->getTweet($id);
        $likes = $api->getLikes($id);

        if (!$tweet) {
            throw $this->createNotFoundException('Tweet introuvable');
        }

        return $this->render('tweet/show.html.twig', [
            'tweet' => $tweet,
            'likes' => $likes,
        ]);
    }
}
