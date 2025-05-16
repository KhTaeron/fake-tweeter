<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use App\Service\NotificationApiClientService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{

    #[Route('', name: 'get_notifications')]
    public function index(NotificationRepository $repo, SessionInterface $session, NotificationApiClientService $api): Response
    {
        $api->setTokenFromSession($session);
        $user = $this->getUser();
        $notifs = $api->getNotifications();

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifs,
        ]);
    }

    // #[Route('/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    // public function markRead(Notification $notification): Response
    // {
    //     $notification->setIsRead(true);
    //     $this->getDoctrine()->getManager()->flush();

    //     return $this->redirectToRoute('notifications');
    // }

}
