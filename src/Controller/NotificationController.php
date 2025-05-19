<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationApiClientService;
use App\Service\UserApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{

    #[Route('', name: 'get_notifications')]
    public function index(NotificationRepository $repo, SessionInterface $session, NotificationApiClientService $api, UserApiClientService $userApi): Response
    {
        $api->setTokenFromSession($session);
        $userApi->setTokenFromSession($session);

        $me = $userApi->getMe();
        $notifs = $api->getNotifications();

        return $this->render('notification/index.html.twig', [
            'profileMe' => $me,
            'notifications' => $notifs,
        ]);
    }

    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    public function markRead(int $id, Notification $notification, SessionInterface $session, NotificationApiClientService $api): Response
    {
        $api->setTokenFromSession($session);
        $ok = $api->markAsRead($id);

        if (!$ok) {
            $this->addFlash('error', 'Impossible de mettre en lu');
        } else {
            $this->addFlash('success', 'Notification marquée comme lue');
        }

        return $this->redirectToRoute('get_notifications');
    }

}
