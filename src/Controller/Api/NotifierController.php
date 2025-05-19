<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Tag(name: 'Notification')]
#[OA\Security(name: 'bearerAuth')]
#[Route('/api/notifications')]
class NotifierController extends AbstractController
{
    #[Route('', methods: ['GET'], name: 'list_notifications')]
    #[OA\Get(
        path: '/api/notifications',
        summary: 'Liste toutes les notifications d\'un utilisateur',
        responses: [
            new OA\Response(response: 200, description: 'Liste des notifications'),
            new OA\Response(response: 401, description: 'Non authentifié')
        ]
    )]
    #[OA\Security(name: 'bearerAuth')]
    public function getNotifications(NotificationRepository $notificationRepository, NotificationService $notificationService): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], 401);
        }

        $notifications = $notificationRepository->findBy(['target' => $user], ['createdAt' => 'DESC']);
        $formattedNotifs = $notificationService->formatList($notifications);

        return $this->json($formattedNotifs);
    }

    #[Route('/{id}/read', name: 'mark_notification_read', methods: ['PUT'])]
    #[OA\Put(
        path: '/api/notifications/{id}/read',
        summary: 'Marque une notification comme lue',
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Notification marquée comme lue'),
            new OA\Response(response: 404, description: 'Notification introuvable'),
            new OA\Response(response: 500, description: 'Erreur interne')
        ]
    )]
    #[OA\Security(name: 'bearerAuth')]
    public function markAsRead(int $id, NotificationService $notificationService, NotificationRepository $notificationRepository ): JsonResponse
    {
        $notification = $notificationRepository->find($id);

        if (!$notification) {
            return $this->json(['error' => 'Notification introuvable'], 404);
        }

        try {
            $notificationService->markAsRead($notification);
            return $this->json(['success' => true]);
        } catch (\Throwable $e) {
            return $this->json([
                'error' => 'Erreur lors de la mise à jour',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
