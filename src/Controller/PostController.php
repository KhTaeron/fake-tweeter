<?php

namespace App\Controller;

use App\Service\TweetApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tweet')]
class PostController extends AbstractController
{
    #[Route('/{id}', name: 'tweet_detail', requirements: ['id' => '\d+'])]
    public function show(int $id, SessionInterface $session, TweetApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

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

    #[Route('/{id}/like', name: 'tweet_like', methods: ['POST'])]
    public function like(int $id, SessionInterface $session, TweetApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $ok = $api->likeTweet($id);

        if (!$ok) {
            $this->addFlash('error', 'Impossible de liker le tweet.');
        } else {
            $this->addFlash('success', 'Tweet liké avec succès.');
        }

        return $this->redirectToRoute('tweet_detail', ['id' => $id]);
    }

    #[Route('/{id}/update', name: 'update_post', methods: ['PUT'])]
    public function updateTweet(
        int $id,
        Request $request,
        SessionInterface $session,
        TweetApiClientService $api
    ): Response {
        $api->setTokenFromSession($session);

        $content = $request->request->get('content');

        $ok = $api->updateTweet($id, ['content' => $content]);

        $this->addFlash($ok ? 'success' : 'error',
            $ok ? 'Tweet modifié avec succès.' : 'Impossible de modifier le tweet.');

        return $this->redirectToRoute('tweet_detail', ['id' => $id]);
    }

}