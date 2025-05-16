<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\TweetService;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/api/tweets')]
class TweetController extends AbstractController
{
    #[Route('', methods: ['GET'], name: 'tweets_list')]
    #[OA\Get(
        path: '/tweets',
        summary: 'Liste tous les tweets',
        tags: ['Tweets'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des tweets')
        ]
    )]
    public function list(TweetService $tweets): JsonResponse
    {
        return $this->json($tweets->list());
    }


    #[Route('/search', name: 'api_tweets_search', methods: ['GET'])]
    public function search(Request $request, TweetService $tweets): JsonResponse
    {
        try {
            $keyword = $request->query->get('q', '');

            $results = $tweets->search($keyword);

            return $this->json(array_map(function (\App\Entity\Tweet $tweet) {
                $tweeter = $tweet->getTweeter();
                return [
                    'id' => $tweet->getId(),
                    'content' => $tweet->getContent(),
                    'publicationDate' => $tweet->getPublicationDate()->format('Y-m-d H:i'),
                    'tweeter' => $tweeter ? [
                        'id' => $tweeter->getId(),
                        'pseudo' => $tweeter->getPseudo(),
                    ] : null,
                ];
            }, $results));
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur interne',
                'type' => get_class($e),
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
        }
    }

    #[Route('/{id}', methods: ['GET'])]
    #[OA\Get(
        path: '/tweets/{id}',
        summary: 'Afficher un tweet par ID',
        tags: ['Tweets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Tweet trouvé'),
            new OA\Response(response: 404, description: 'Tweet introuvable')
        ]
    )]
    public function show(int $id, TweetService $tweetService): JsonResponse
    {
        $user =$this->getUser();

        $tweet = $tweetService->getFullEntity($id);

        $tweeter = $tweet->getTweeter();

        if (!$tweet) {
            return $this->json(['error' => 'Tweet introuvable'], 404);
        }

        $formattedTweet = $tweetService->formatTweet($tweet);

        if ($tweeter == $user) {
            $formattedTweet['isCurrentUser'] = true;
        } else {
            $formattedTweet['isCurrentUser'] = false;
        }
        
        return $this->json($formattedTweet);
    }

    #[Route('/{id}/likes', name: 'tweet_like_add', methods: ['POST'])]
    public function like(int $id, TweetService $tweetService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $tweetService->toggleLike($id, $user);
            return $this->json(['success' => true], 201);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    #[Route('/create', methods: ['POST'], name: 'tweet_create')]
    public function create(Request $request, TweetService $tweetService, UserRepository $users): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $payload = $request->toArray();

        try {
                $tweetService->create($payload, $user);
            return $this->json(['success' => true], 201);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }

    }
    #[Route('/{id}/delete', methods: ['DELETE'], name: 'tweet_delete')]
    public function deleteTweet(int $id, TweetService $tweetService): JsonResponse
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $tweetService->delete($id, $user);
        } catch (AccessDeniedException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(null, 204);
    }

    #[Route('/{id}/likes', methods: ['GET'], name: 'tweet_likes')]
    public function likes(int $id, TweetService $tweets): JsonResponse
    {
        try {
            $likes = $tweets->getLikes($id);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return $this->json($likes);
    }
    #[Route('/{id}/update', methods: ['PUT'], name: 'tweet_update')]
    public function update(int $id, Request $request, TweetService $tweets): JsonResponse
    {
        
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $payload = $request->toArray();

        try {
            $updated = $tweets->updateTweet($id, $payload, $user); // ⬅️ on passe $user

        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 403);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json($updated);
    }





}
