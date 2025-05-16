<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/notifications')]
class NotifierController extends AbstractController
{
    #[Route('', methods: ['GET'], name: 'list_notifications')]
    #[OA\Get(
        path: '/api/notifications',
        summary: 'Liste toutes les notifications d\'un utilisateur',
        tags: ['Notification'],
        responses: [
            new OA\Response(response: 200, description: 'Liste des notifications')
        ]
    )]
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