<?php
namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Utilisateurs')]
#[OA\Security(name: 'bearerAuth')]
#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('/me', name: 'api_user_me', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/me',
        summary: 'Récupérer les infos de l’utilisateur connecté',
        responses: [
            new OA\Response(response: 200, description: 'Infos utilisateur'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function me(UserService $service): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        return $this->json($service->getMe($user));
    }

    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}',
        summary: 'Afficher un utilisateur par ID',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Infos utilisateur'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ]
    )]
    public function show(int $id, UserService $service): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = $service->getById($id, $currentUser instanceof User ? $currentUser : null);
        if (!$data) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        return $this->json($data);
    }

    #[Route('/{id}/followers', name: 'api_user_followers', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}/followers',
        summary: 'Afficher les followers d’un utilisateur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Liste des followers'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ]
    )]
    public function followers(int $id, UserService $service): JsonResponse
    {
        $user = $service->getUserEntityById($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        return $this->json($service->getFollowers($user));
    }

    #[Route('/{id}/subscriptions', name: 'api_user_subscriptions', methods: ['GET'])]
    #[OA\Get(
        path: '/api/users/{id}/subscriptions',
        summary: 'Afficher les abonnements d’un utilisateur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Liste des abonnements'),
            new OA\Response(response: 404, description: 'Utilisateur introuvable')
        ]
    )]
    public function subscriptions(int $id, UserService $service): JsonResponse
    {
        $user = $service->getUserEntityById($id);
        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }
        return $this->json($service->getFollowing($user));
    }

    #[Route('/me', name: 'api_user_update_me', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/users/me',
        summary: 'Modifier les infos de l’utilisateur connecté',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'pseudo', type: 'string', example: 'newPseudo'),
                    new OA\Property(property: 'fullName', type: 'string', example: 'Jean Dupont'),
                    new OA\Property(property: 'description', type: 'string', example: 'Fan de dev')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Mise à jour réussie'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 422, description: 'Pseudo invalide')
        ]
    )]
    public function updateMe(Request $request, UserService $service): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }
        $payload = json_decode($request->getContent(), true);
        $pseudo = $payload['pseudo'] ?? null;
        $fullName = $payload['fullName'] ?? null;
        $description = $payload['description'] ?? null;

        if (!$pseudo || trim($pseudo) === '') {
            return $this->json(['error' => 'Pseudo invalide'], 422);
        }

        try {
            $service->updateUser($user, $pseudo, $fullName, $description);
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/me', name: 'api_user_delete_me', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/users/me',
        summary: 'Supprimer le compte de l’utilisateur connecté',
        responses: [
            new OA\Response(response: 204, description: 'Utilisateur supprimé'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    public function deleteMe(UserService $service): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $service->deleteUser($user);
            return $this->json(['message' => 'Utilisateur supprimé'], 204);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/{id}/follow', name: 'toggle_user_subscription', methods: ['POST'])]
    #[OA\Post(
        path: '/api/users/{id}/follow',
        summary: 'S’abonner ou se désabonner d’un utilisateur',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Action effectuée'),
            new OA\Response(response: 401, description: 'Non authentifié'),
            new OA\Response(response: 400, description: 'Erreur logique')
        ]
    )]
    public function like(int $id, UserService $userService): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        try {
            $count = $userService->toggleSubscription($id, $user);
            return $this->json([
                'success' => true,
                'subscriptionsCount' => $count,
            ]);
        } catch (\Throwable $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }
}
