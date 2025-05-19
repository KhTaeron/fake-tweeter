<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use App\Service\TweetService;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;

#[OA\Tag(name: 'Tweets')]
#[Route('/api/tweets')]
class TweetController extends AbstractController
{
    #[OA\Get(summary: 'Liste tous les tweets')]
    #[OA\Response(response: 200, description: 'Liste des tweets')]
    #[Route('', methods: ['GET'], name: 'tweets_list')]
    public function list(TweetService $tweets): JsonResponse
    {
        return $this->json($tweets->list());
    }

    #[OA\Get(summary: 'Rechercher des tweets')]
    #[OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string'))]
    #[OA\Response(response: 200, description: 'Tweets trouvés')]
    #[Route('/search', name: 'api_tweets_search', methods: ['GET'])]
    public function search(Request $request, TweetService $tweets): JsonResponse
    {
        try {
            $keyword = $request->query->get('q', '');

            $results = $tweets->search($keyword);

            return $this->json($results);
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

    #[OA\Get(summary: 'Afficher un tweet par ID')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Tweet trouvé')]
    #[OA\Response(response: 404, description: 'Tweet introuvable')]
    #[Route('/{id}', methods: ['GET'])]
    public function show(int $id, TweetService $tweetService): JsonResponse
    {
        $tweet = $tweetService->getFullEntity($id);
        if (!$tweet) {
            return $this->json(['error' => 'Tweet introuvable'], 404);
        }
        $formattedTweet = $tweetService->formatTweet($tweet);
        $formattedTweet['isCurrentUser'] = $tweet->getTweeter() === $this->getUser();
        return $this->json($formattedTweet);
    }

    #[OA\Post(summary: 'Liker ou unliker un tweet')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 201, description: 'Like modifié')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[Route('/{id}/likes', name: 'tweet_like_add', methods: ['POST'])]
    public function like(int $id, TweetService $tweetService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);
        $tweetService->toggleLike($id, $user);
        return $this->json(['success' => true], 201);
    }

    #[OA\Post(summary: 'Créer un tweet')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['content'],
        properties: [
            new OA\Property(property: 'content', type: 'string', example: 'Mon nouveau tweet')
        ]
    ))]
    #[OA\Response(response: 201, description: 'Tweet créé')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[Route('/create', methods: ['POST'], name: 'tweet_create')]
    public function create(Request $request, TweetService $tweetService, UserRepository $users): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);
        $payload = $request->toArray();
        $tweetService->create($payload, $user);
        return $this->json(['success' => true], 201);
    }

    #[OA\Delete(summary: 'Supprimer un tweet')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 204, description: 'Tweet supprimé')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 403, description: 'Accès refusé')]
    #[Route('/{id}/delete', methods: ['DELETE'], name: 'tweet_delete')]
    public function deleteTweet(int $id, TweetService $tweetService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);
        $tweetService->delete($id, $user);
        return new JsonResponse(null, 204);
    }

    #[OA\Get(summary: 'Lister les likes dun tweet')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\Response(response: 200, description: 'Liste des likes')]
    #[Route('/{id}/likes', methods: ['GET'], name: 'tweet_likes')]
    public function likes(int $id, TweetService $tweets): JsonResponse
    {
        $likes = $tweets->getLikes($id);
        return $this->json($likes);
    }

    #[OA\Put(summary: 'Mettre à jour un tweet')]
    #[OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['content'],
        properties: [
            new OA\Property(property: 'content', type: 'string', example: 'Tweet modifié')
        ]
    ))]
    #[OA\Response(response: 200, description: 'Tweet mis à jour')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[OA\Response(response: 422, description: 'Erreur de validation')]
    #[Route('/{id}/update', methods: ['PUT'], name: 'tweet_update')]
    public function update(int $id, Request $request, TweetService $tweets): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);
        $payload = $request->toArray();
        $updated = $tweets->updateTweet($id, $payload, $user);
        return $this->json($updated);
    }

    #[OA\Post(summary: 'Retweeter un tweet avec un commentaire (facultatif)')]
    #[OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['original_tweet_id'],
        properties: [
            new OA\Property(property: 'original_tweet_id', type: 'integer', example: 42),
            new OA\Property(property: 'content', type: 'string', nullable: true, example: 'Mon avis sur ce tweet')
        ]
    ))]
    #[OA\Response(response: 201, description: 'Retweet réussi')]
    #[OA\Response(response: 401, description: 'Non authentifié')]
    #[Route('/retweet', methods: ['POST'], name: 'tweet_retweet')]
    public function retweetTweet(Request $request, TweetService $tweetService, LoggerInterface $logger): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) return $this->json(['error' => 'Non authentifié'], 401);
        $payload = $request->toArray();
        $retweet = $tweetService->retweet($payload['original_tweet_id'], $user, $payload['content'] ?? '', $logger);
        return $this->json(['success' => true, 'retweet_id' => $retweet['id']], 201);
    }
}
