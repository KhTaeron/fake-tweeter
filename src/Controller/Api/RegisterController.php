<?php
namespace App\Controller\Api;

use App\Service\UserService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Authentification')]
#[Route('/api/register')]
class RegisterController extends AbstractController
{
    #[Route('', name: 'api_register', methods: ['POST'])]
    #[OA\Post(
        path: '/api/register',
        summary: 'Inscription d\'un nouvel utilisateur',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['pseudo', 'password'],
                properties: [
                    new OA\Property(property: 'pseudo', type: 'string', example: 'john_doe'),
                    new OA\Property(property: 'password', type: 'string', example: 'mySecret123')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur créé'),
            new OA\Response(response: 409, description: 'Pseudo déjà pris'),
            new OA\Response(response: 422, description: 'Champs manquants'),
            new OA\Response(response: 500, description: 'Erreur serveur')
        ]
    )]
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
