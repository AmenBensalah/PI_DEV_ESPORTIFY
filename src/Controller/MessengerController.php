<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/messages')]
class MessengerController extends AbstractController
{
    #[Route('', name: 'app_messages_index', methods: ['GET'])]
    public function index(
        Request $request,
        Connection $connection,
        UserRepository $userRepository,
        Packages $packages,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $conversations = $this->fetchConversations($connection, $me->getId(), $packages);
        $selectedUserId = (int) $request->query->get('u', 0);
        if ($selectedUserId <= 0 && !empty($conversations)) {
            $selectedUserId = (int) $conversations[0]['userId'];
        }

        return $this->render('messenger/index.html.twig', [
            'conversations' => $conversations,
            'contacts' => $userRepository->createQueryBuilder('u')
                ->andWhere('u.id != :me')
                ->setParameter('me', $me->getId())
                ->orderBy('u.id', 'DESC')
                ->setMaxResults(40)
                ->getQuery()
                ->getResult(),
            'selectedUserId' => $selectedUserId,
            'messageSendToken' => $csrfTokenManager->getToken('message_send')->getValue(),
            'messageReadToken' => $csrfTokenManager->getToken('message_read')->getValue(),
        ]);
    }

    #[Route('/count', name: 'app_messages_count', methods: ['GET'])]
    public function count(Connection $connection): JsonResponse
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['unread' => 0], Response::HTTP_UNAUTHORIZED);
        }

        $unread = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM chat_messages WHERE recipient_id = :me AND is_read = 0',
            ['me' => $me->getId()]
        );

        return new JsonResponse(['unread' => $unread]);
    }

    #[Route('/panel', name: 'app_messages_panel', methods: ['GET'])]
    public function panel(Connection $connection, Packages $packages): JsonResponse
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['conversations' => []], Response::HTTP_UNAUTHORIZED);
        }

        return new JsonResponse([
            'conversations' => $this->fetchConversations($connection, $me->getId(), $packages),
        ]);
    }

    #[Route('/search', name: 'app_messages_search', methods: ['GET'])]
    public function search(Request $request, Connection $connection, Packages $packages): JsonResponse
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Non autorise'], Response::HTTP_UNAUTHORIZED);
        }

        $query = trim((string) $request->query->get('q', ''));
        $like = '%' . $query . '%';

        $conversations = $this->fetchConversations($connection, $me->getId(), $packages);
        if ($query !== '') {
            $needle = mb_strtolower($query);
            $conversations = array_values(array_filter(
                $conversations,
                static fn (array $row): bool => mb_strpos(mb_strtolower((string) ($row['name'] ?? '')), $needle) !== false
            ));
        }

        $contactRows = $connection->fetchAllAssociative(
            'SELECT id, pseudo, nom, email, avatar
             FROM `user`
             WHERE id != :me
               AND (:q = \'\' OR pseudo LIKE :like OR nom LIKE :like OR email LIKE :like)
             ORDER BY id DESC
             LIMIT 40',
            [
                'me' => $me->getId(),
                'q' => $query,
                'like' => $like,
            ]
        );

        $contacts = array_map(function (array $row) use ($packages): array {
            $name = $this->displayNameFromRow($row);
            return [
                'userId' => (int) $row['id'],
                'name' => $name,
                'avatar' => $row['avatar'] ? $packages->getUrl('uploads/avatars/' . $row['avatar']) : null,
                'initial' => mb_strtoupper(mb_substr($name, 0, 1)),
            ];
        }, $contactRows);

        return new JsonResponse([
            'conversations' => $conversations,
            'contacts' => $contacts,
        ]);
    }

    #[Route('/thread/{id}', name: 'app_messages_thread', methods: ['GET'])]
    public function thread(int $id, Connection $connection, Packages $packages): JsonResponse
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Non autorise'], Response::HTTP_UNAUTHORIZED);
        }
        if ($id === $me->getId()) {
            return new JsonResponse(['error' => 'Conversation invalide'], Response::HTTP_BAD_REQUEST);
        }

        $other = $connection->fetchAssociative(
            'SELECT id, pseudo, nom, email, avatar FROM `user` WHERE id = :id',
            ['id' => $id]
        );
        if (!$other) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $rows = $connection->fetchAllAssociative(
            'SELECT id, sender_id, recipient_id, body, type, call_url, is_read, created_at
             FROM chat_messages
             WHERE (sender_id = :me AND recipient_id = :other)
                OR (sender_id = :other AND recipient_id = :me)
             ORDER BY id ASC
             LIMIT 400',
            ['me' => $me->getId(), 'other' => $id]
        );

        $otherName = $this->displayNameFromRow($other);

        return new JsonResponse([
            'otherUser' => [
                'id' => (int) $other['id'],
                'name' => $otherName,
                'avatar' => $other['avatar'] ? $packages->getUrl('uploads/avatars/' . $other['avatar']) : null,
                'initial' => mb_strtoupper(mb_substr($otherName, 0, 1)),
            ],
            'messages' => array_map(static fn (array $row): array => [
                'id' => (int) $row['id'],
                'senderId' => (int) $row['sender_id'],
                'recipientId' => (int) $row['recipient_id'],
                'body' => (string) $row['body'],
                'type' => (string) $row['type'],
                'callUrl' => $row['call_url'] ? (string) $row['call_url'] : null,
                'isRead' => (bool) $row['is_read'],
                'createdAt' => (new \DateTimeImmutable((string) $row['created_at']))->format('d/m/Y H:i'),
            ], $rows),
        ]);
    }

    #[Route('/send/{id}', name: 'app_messages_send', methods: ['POST'])]
    public function send(int $id, Request $request, Connection $connection): JsonResponse
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Non autorise'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->isCsrfTokenValid('message_send', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF invalide'], Response::HTTP_BAD_REQUEST);
        }
        if ($id === $me->getId()) {
            return new JsonResponse(['error' => 'Conversation invalide'], Response::HTTP_BAD_REQUEST);
        }

        $exists = (int) $connection->fetchOne('SELECT COUNT(*) FROM `user` WHERE id = :id', ['id' => $id]);
        if ($exists === 0) {
            return new JsonResponse(['error' => 'Utilisateur introuvable'], Response::HTTP_NOT_FOUND);
        }

        $type = trim((string) $request->request->get('type', 'text'));
        $body = trim((string) $request->request->get('body', ''));
        $callUrl = trim((string) $request->request->get('callUrl', ''));

        if (!in_array($type, ['text', 'gif', 'call_audio', 'call_video'], true)) {
            $type = 'text';
        }

        if ($type === 'text' && $body === '') {
            return new JsonResponse(['error' => 'Message vide'], Response::HTTP_BAD_REQUEST);
        }
        if ($type === 'gif' && $body === '') {
            return new JsonResponse(['error' => 'GIF invalide'], Response::HTTP_BAD_REQUEST);
        }
        if ($type !== 'text' && $callUrl === '') {
            if ($type === 'call_audio' || $type === 'call_video') {
                $callUrl = sprintf('https://meet.jit.si/esportify-%s-%d-%d', $type, $me->getId(), time());
                $body = $type === 'call_audio' ? 'Appel vocal' : 'Appel video';
            }
        }

        $connection->insert('chat_messages', [
            'sender_id' => $me->getId(),
            'recipient_id' => $id,
            'body' => $body,
            'type' => $type,
            'call_url' => $callUrl !== '' ? $callUrl : null,
            'is_read' => 0,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/read/{id}', name: 'app_messages_read', methods: ['POST'])]
    public function markRead(int $id, Request $request, Connection $connection): JsonResponse
    {
        $me = $this->getUser();
        if (!$me instanceof User) {
            return new JsonResponse(['error' => 'Non autorise'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->isCsrfTokenValid('message_read', (string) $request->request->get('_token'))) {
            return new JsonResponse(['error' => 'CSRF invalide'], Response::HTTP_BAD_REQUEST);
        }

        $connection->executeStatement(
            'UPDATE chat_messages SET is_read = 1
             WHERE sender_id = :other AND recipient_id = :me AND is_read = 0',
            ['other' => $id, 'me' => $me->getId()]
        );

        $unread = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM chat_messages WHERE recipient_id = :me AND is_read = 0',
            ['me' => $me->getId()]
        );

        return new JsonResponse(['ok' => true, 'unread' => $unread]);
    }

    /**
     * @return array<int, array{
     *   userId:int,name:string,avatar:?string,initial:string,lastMessage:string,lastType:string,lastAt:string,unread:int
     * }>
     */
    private function fetchConversations(Connection $connection, int $userId, Packages $packages): array
    {
        $rows = $connection->fetchAllAssociative(
            'SELECT
                x.other_id,
                x.unread_count,
                m.body,
                m.type,
                m.created_at,
                u.id AS uid,
                u.pseudo,
                u.nom,
                u.email,
                u.avatar
             FROM (
                SELECT
                    CASE WHEN sender_id = :me THEN recipient_id ELSE sender_id END AS other_id,
                    MAX(id) AS last_id,
                    SUM(CASE WHEN recipient_id = :me AND is_read = 0 THEN 1 ELSE 0 END) AS unread_count
                FROM chat_messages
                WHERE sender_id = :me OR recipient_id = :me
                GROUP BY other_id
             ) x
             JOIN chat_messages m ON m.id = x.last_id
             JOIN `user` u ON u.id = x.other_id
             ORDER BY m.created_at DESC
             LIMIT 40',
            ['me' => $userId]
        );

        return array_map(function (array $row) use ($packages): array {
            $name = $this->displayNameFromRow($row);

            return [
                'userId' => (int) $row['uid'],
                'name' => $name,
                'avatar' => $row['avatar'] ? $packages->getUrl('uploads/avatars/' . $row['avatar']) : null,
                'initial' => mb_strtoupper(mb_substr($name, 0, 1)),
                'lastMessage' => (string) $row['body'],
                'lastType' => (string) $row['type'],
                'lastAt' => (new \DateTimeImmutable((string) $row['created_at']))->format('d/m H:i'),
                'unread' => (int) $row['unread_count'],
            ];
        }, $rows);
    }

    private function displayNameFromRow(array $row): string
    {
        $pseudo = isset($row['pseudo']) ? trim((string) $row['pseudo']) : '';
        if ($pseudo !== '') {
            return $pseudo;
        }

        $nom = isset($row['nom']) ? trim((string) $row['nom']) : '';
        if ($nom !== '') {
            return $nom;
        }

        return (string) ($row['email'] ?? 'Utilisateur');
    }
}
