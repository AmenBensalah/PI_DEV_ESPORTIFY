<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/count', name: 'app_notifications_count', methods: ['GET'])]
    public function count(NotificationRepository $notificationRepository): JsonResponse
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['unread' => 0]);
        }

        return new JsonResponse([
            'unread' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/panel', name: 'app_notifications_panel', methods: ['GET'])]
    public function panel(NotificationRepository $notificationRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        return $this->render('notification/_panel.html.twig', [
            'notifications' => $notificationRepository->findLatestForUser($user, 20),
            'unreadCount' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/{id}/read', name: 'app_notifications_read', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function markRead(
        Notification $notification,
        Request $request,
        NotificationRepository $notificationRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user instanceof User || $notification->getRecipient()?->getId() !== $user->getId()) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        if (!$this->isCsrfTokenValid('notifications', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_BAD_REQUEST);
        }

        if (!$notification->isRead()) {
            $notification->setIsRead(true);
            $entityManager->flush();
        }

        return new JsonResponse([
            'ok' => true,
            'unread' => $notificationRepository->countUnreadForUser($user),
        ]);
    }

    #[Route('/read-all', name: 'app_notifications_read_all', methods: ['POST'])]
    public function markAllRead(
        Request $request,
        NotificationRepository $notificationRepository
    ): JsonResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isCsrfTokenValid('notifications', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'Invalid CSRF token'], Response::HTTP_BAD_REQUEST);
        }

        $notificationRepository->markAllAsReadForUser($user);

        return new JsonResponse([
            'ok' => true,
            'unread' => 0,
        ]);
    }
}

