<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Entity\User;
use App\Service\TweetService;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use OpenApi\Attributes as OA;

#[Route('/tweets')]
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
    public function list(TweetService $tweets): JsonResponse {
        return $this->json($tweets->list());
    }

    #[Route('/{id<\d+>}', methods: ['GET'], name: 'tweet_show')]
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
    #[Route('/{id<\d+>}', methods: ['GET'], name: 'tweet_show')]
    public function show(int $id, TweetService $tweets, Request $request): Response {
        $tweet = $tweets->getFullEntity($id); // méthode à créer, retourne l'entité complète

        if (!$tweet) {
            throw $this->createNotFoundException('Tweet introuvable');
        }

        return $this->render('tweet/show.html.twig', [
            'tweet' => $tweet,
        ]);
    }

    #[Route('', methods: ['POST'], name: 'tweet_create')]
    #[OA\Post(
        path: '/tweets',
        summary: 'Créer un nouveau tweet',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content', 'tweeterId'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 280),
                    new OA\Property(property: 'tweeterId', type: 'integer')
                ]
            )
        ),
        tags: ['Tweets'],
        responses: [
            new OA\Response(response: 201, description: 'Tweet créé'),
            new OA\Response(response: 422, description: 'Erreur de validation'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ]
    )]
    public function create(Request $request, TweetService $tweets, UserRepository $users, ValidatorInterface $validator): JsonResponse {
        $payload = $request->toArray();

        $violations = $validator->validate(
            $payload,
            new Assert\Collection([
                'fields' => [
                    'content'   => [new Assert\NotBlank(), new Assert\Length(max: 280)],
                    'tweeterId' => [new Assert\NotBlank(), new Assert\Positive()],
                ],
                'allowExtraFields' => true,
            ])
        );

        if (\count($violations) > 0) {
            return $this->json(['error' => (string) $violations], 422);
        }

        $user = $users->find($payload['tweeterId']);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        try {
            $data = $tweets->create($payload, $user);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json($data, 201, [
            'Location' => $this->generateUrl('tweet_show', ['id' => $data['id']]),
        ]);
    }

    #[Route('/{id}', methods: ['DELETE'], name: 'tweet_delete')]
    #[OA\Delete(
        path: '/tweets/{id}',
        summary: 'Supprimer un tweet',
        tags: ['Tweets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Tweet supprimé'),
            new OA\Response(response: 404, description: 'Tweet introuvable')
        ]
    )]
    public function delete(int $id, TweetService $tweets): JsonResponse {
        try {
            $tweets->delete($id);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return new JsonResponse(null, 204);
    }

    #[Route('/{id}/likes', methods: ['GET'], name: 'tweet_likes')]
    #[OA\Get(
        path: '/tweets/{id}/likes',
        summary: 'Afficher les utilisateurs ayant liké un tweet',
        tags: ['Tweets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des likes'),
            new OA\Response(response: 404, description: 'Tweet introuvable')
        ]
    )]
    public function likes(int $id, TweetService $tweets): JsonResponse {
        try {
            $likes = $tweets->getLikes($id);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }

        return $this->json($likes);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'tweet_update')]
    #[OA\Put(
        path: '/tweets/{id}',
        summary: 'Modifier le contenu d’un tweet',
        tags: ['Tweets'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['content'],
                properties: [
                    new OA\Property(property: 'content', type: 'string', maxLength: 280)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Tweet modifié'),
            new OA\Response(response: 404, description: 'Tweet introuvable'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function update(int $id, Request $request, TweetService $tweets): JsonResponse {
        $payload = $request->toArray();

        try {
            $updated = $tweets->update($id, $payload);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 422);
        }

        return $this->json($updated);
    }

    #[Route('/home', name: 'tweets_home')]
    public function explore(Request $request, TweetService $tweets): Response
    {
        $keyword = $request->query->get('q', '');
        $results = $tweets->search($keyword);

        return $this->render('tweet/home.html.twig', [
            'tweets' => $results,
            'keyword' => $keyword,
        ]);
    }

}
