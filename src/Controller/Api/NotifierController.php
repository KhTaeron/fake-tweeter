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
}