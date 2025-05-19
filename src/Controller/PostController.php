<?php

namespace App\Controller;

use App\Service\TweetApiClientService;
use App\Service\UserApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;


#[Route('/tweet')]
class PostController extends AbstractController
{
    #[Route('/{id}', name: 'tweet_detail', requirements: ['id' => '\d+'])]
    public function show(int $id, SessionInterface $session, TweetApiClientService $api, UserApiClientService $userApi): Response
    {
        $api->setTokenFromSession($session);
        $userApi->setTokenFromSession($session);

        $me = $userApi->getMe();

        $tweet = $api->getTweet($id);
        $likes = $api->getLikes($id);

        if (!$tweet) {
            throw $this->createNotFoundException('Tweet introuvable');
        }

        return $this->render('tweet/show.html.twig', [
            'profileMe' => $me,
            'tweet' => $tweet,
            'likes' => $likes,
        ]);
    }
    
    #[Route('/{id}/like', name: 'like_post', methods: ['POST'])]
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
    public function updateTweet(int $id, Request $request, SessionInterface $session, TweetApiClientService $api): Response
    {
        $api->setTokenFromSession($session);

        $content = $request->request->get('content');

        $ok = $api->updateTweet($id, ['content' => $content]);

        $this->addFlash(
            $ok ? 'success' : 'error',
            $ok ? 'Tweet modifié avec succès.' : 'Impossible de modifier le tweet.'
        );

        return $this->redirectToRoute('tweet_detail', ['id' => $id]);
    }

    #[Route('/{id}/delete', name: 'delete_post', methods: ['DELETE'])]
    public function deleteTweet(int $id, SessionInterface $session, TweetApiClientService $api): Response
    {

        $api->setTokenFromSession($session);

        $ok = $api->deleteTweet($id);

        $this->addFlash(
            $ok ? 'success' : 'error',
            $ok ? 'Tweet supprimé avec succès.' : 'Impossible de supprimer le tweet.'
        );

        return $this->redirectToRoute('tweets_home', ['id' => $id]);
    }

    #[Route('/{id}/retweet', name: 'retweet_post', methods: ['POST'])]
    public function retweetTweet(
        int $id,
        Request $request,
        SessionInterface $session,
        TweetApiClientService $api,
        LoggerInterface $logger
    ): Response {
        $logger->info('📤 Début du retweet', [
            'tweet_id' => $id,
            'user' => $session->get('user') ?? 'inconnu',
        ]);

        $api->setTokenFromSession($session);

        $content = $request->request->get('content');
        $logger->debug('✏️ Contenu du retweet (commentaire) : ' . ($content ?? '[aucun]'));

        $ok = $api->retweetTweet([
            'original_tweet_id' => $id,
            'content' => $content,
        ]);

        $logger->info($ok ? '✅ Retweet envoyé avec succès' : '❌ Échec du retweet', [
            'tweet_id' => $id,
            'with_comment' => $content !== null,
        ]);

        $this->addFlash(
            $ok ? 'success' : 'error',
            $ok ? 'Tweet modifié avec succès.' : 'Impossible de modifier le tweet.'
        );

        return $this->redirectToRoute('tweets_home');
    }

}