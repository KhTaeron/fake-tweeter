<?php
// src/Controller/HomeController.php

namespace App\Controller;

use App\Service\TweetApiClientService;
use App\Service\UserApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/home')]
class HomeController extends AbstractController
{
    #[Route('', name: 'tweets_home')]
    public function index(
        Request $request,
        SessionInterface $session,
        TweetApiClientService $api,
        UserApiClientService $userApi
    ): Response {
        $api->setTokenFromSession($session);
        $userApi->setTokenFromSession($session);

        $keyword = $request->query->get('q', '');
        $tweets = $api->getTweets($keyword);

        foreach ($tweets as &$tweet) {
            $likes = $api->getLikes($tweet['id']);
            $tweet['likes_count'] = count($likes);
        }

        $user = $userApi->getMe(); // 💡 c'est ici qu'on récupère le user connecté

        return $this->render('tweet/home.html.twig', [
            'tweets' => $tweets,
            'keyword' => $keyword,
            'user' => $user, // ✅ on passe le user au template
        ]);
    }

    #[Route('/create', name: 'tweet_create_submit', methods: ['POST'])]
    public function create(
        Request $request,
        SessionInterface $session,
        TweetApiClientService $api
    ): Response {
        $content = $request->request->get('content');

        $api->setTokenFromSession($session);

        $ok = $api->createTweet(['content' => $content]);

        $this->addFlash(
            $ok ? 'success' : 'error',
            $ok ? 'Tweet publié avec succès.' : 'Impossible de publier le tweet.'
        );

        return $this->redirectToRoute('tweets_home');
    }
}
