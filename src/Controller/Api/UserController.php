<?php
namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users')]
class UserController extends AbstractController
{
    #[Route('/me', name: 'api_user_me', methods: ['GET'])]
    public function me(UserService $service): JsonResponse
    {
        $user = $this->getUser(); // récupère l'utilisateur connecté

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        return $this->json($service->getMe($user));
    }


    #[Route('/{id}', name: 'api_user_show', methods: ['GET'])]
    public function show(int $id, UserService $service): JsonResponse
    {
        $currentUser = $this->getUser();
        $data = $service->getById($id, $currentUser instanceof User ? $currentUser : null);

        if (!$data) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        return $this->json($data);
    }


    #[Route('/me', name: 'api_user_update_me', methods: ['PUT'])]
    public function updateMe(Request $request, UserService $service): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $payload = json_decode($request->getContent(), true);
        $pseudo = $payload['pseudo'] ?? null;

        if (!$pseudo || trim($pseudo) === '') {
            return $this->json(['error' => 'Pseudo invalide'], 422);
        }

        try {
            $service->updateUser($user, $pseudo);
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    #[Route('/me', name: 'api_user_delete_me', methods: ['DELETE'])]
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
