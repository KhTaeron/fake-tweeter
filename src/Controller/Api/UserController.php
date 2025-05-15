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
        $user = $service->getById($id);

        if (!$user) {
            return $this->json(['error' => 'Utilisateur introuvable'], 404);
        }

        return $this->json($user);
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


}
