<?php

namespace App\Service;

use App\Entity\Notification;
use App\Entity\User;
use App\Enum\NotificationType;
use Doctrine\ORM\EntityManagerInterface;

class NotificationService
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function notifyUser(
        User $recipient,
        string $title,
        string $message,
        ?string $link = null,
        string $type = 'general'
    ): void {
        $notificationType = NotificationType::tryFrom($type) ?? NotificationType::GENERAL;

        $notification = (new Notification())
            ->setRecipient($recipient)
            ->setTitle($title)
            ->setMessage($message)
            ->setLink($link)
            ->setType($notificationType)
            ->setIsRead(false);

        $this->entityManager->persist($notification);
        $this->entityManager->flush();
    }

    /**
     * @param iterable<User> $users
     */
    public function notifyUsers(
        iterable $users,
        string $title,
        string $message,
        ?string $link = null,
        string $type = 'general',
        ?User $exclude = null
    ): void {
        $excludeId = $exclude?->getId();
        $hasAny = false;

        foreach ($users as $recipient) {
            if (!$recipient instanceof User) {
                continue;
            }
            if ($excludeId !== null && $recipient->getId() === $excludeId) {
                continue;
            }

            $notificationType = NotificationType::tryFrom($type) ?? NotificationType::GENERAL;

            $notification = (new Notification())
                ->setRecipient($recipient)
                ->setTitle($title)
                ->setMessage($message)
                ->setLink($link)
                ->setType($notificationType)
                ->setIsRead(false);

            $this->entityManager->persist($notification);
            $hasAny = true;
        }

        if ($hasAny) {
            $this->entityManager->flush();
        }
    }
}

