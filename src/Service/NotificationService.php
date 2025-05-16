<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function addNotification(User $target, string $type, array $payload = []): void
    {
        $notification = new Notification();
        $notification->setTarget($target);
        $notification->setType($type);
        $notification->setPayload($payload);
        $notification->setIsRead(false);
        $notification->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($notification);
        $this->em->flush();
    }

    public function formatNotification(Notification $notification): array
    {
        return [
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'payload' => $notification->getPayload(),
            'isRead' => $notification->isRead(),
            'createdAt' => $notification->getCreatedAt()->format('Y-m-d H:i:s'),
        ];
    }

    public function formatList(array $notifications): array
    {
        return array_map([$this, 'formatNotification'], $notifications);
    }
}
