<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Service\TweetApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/home')]
class HomeController extends AbstractController
{
    #[Route('', name: 'tweets_home')]
    public function index(Request $request, SessionInterface $session, TweetApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $keyword = $request->query->get('q', '');
        $tweets = $api->getTweets($keyword);

        foreach ($tweets as &$tweet) {
            
            $likes = $api->getLikes($tweet['id']);
            $tweet['likes_count'] = count($likes);
        }

        return $this->render('tweet/home.html.twig', [
            'tweets' => $tweets,
            'keyword' => $keyword,
        ]);
    }
}
