<?php
namespace App\Controller\Api;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/register')]
class RegisterController extends AbstractController
{
    #[Route('', name: 'api_register', methods: ['POST'])]
    public function register(Request $request, UserService $service, UserPasswordHasherInterface $hasher): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $pseudo = $data['pseudo'] ?? null;
        $plainPassword = $data['password'] ?? null;

        if (!$pseudo || !$plainPassword) {
            return $this->json(['error' => 'Champs manquants'], 422);
        }

        try {
            $user = $service->register($pseudo, $plainPassword, $hasher);
            return $this->json(['message' => 'Utilisateur créé', 'id' => $user->getId()], 201);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        } catch (\Throwable $e) {
            return $this->json(['error' => 'Erreur serveur'], 500);
        }
    }
}