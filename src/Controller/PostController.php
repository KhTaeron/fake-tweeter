<?php

namespace App\Controller;

use App\Service\TweetApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tweet')]
class PostController extends AbstractController
{
#[Route('/{id}', name: 'tweet_detail', requirements: ['id' => '\d+'])]
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