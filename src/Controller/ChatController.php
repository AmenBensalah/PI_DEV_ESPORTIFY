<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Repository\ChatMessageRepository;
use App\Repository\EquipeRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    #[Route('/equipe/{id}/messages', name: 'app_chat_messages', methods: ['GET'])]
    public function getMessages(
        int $id,
        EquipeRepository $equipeRepository,
        ChatMessageRepository $chatRepository,
        Connection $connection
    ): JsonResponse {
        $equipe = $equipeRepository->find($id);
        
        if (!$equipe) {
            return new JsonResponse(['error' => 'Équipe non trouvée'], 404);
        }

        // Check if user is member or manager
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $rows = $connection->fetchAllAssociative(
            'SELECT m.id, m.message, m.created_at, m.is_read, m.user_id,
                    u.id AS uid, u.pseudo, u.nom
             FROM chat_message m
             LEFT JOIN user u ON u.id = m.user_id
             WHERE m.equipe_id = :teamId
             ORDER BY m.created_at ASC, m.id ASC
             LIMIT 100',
            ['teamId' => $equipe->getId()]
        );

        $data = array_map(static function (array $row): array {
            $uid = isset($row['uid']) ? (int) $row['uid'] : 0;
            $pseudo = 'Esportify AI Analyst';
            if ($uid > 0) {
                $pseudoRaw = isset($row['pseudo']) ? trim((string) $row['pseudo']) : '';
                $nomRaw = isset($row['nom']) ? trim((string) $row['nom']) : '';
                $pseudo = $pseudoRaw !== '' ? $pseudoRaw : ($nomRaw !== '' ? $nomRaw : 'Utilisateur supprimé');
            } elseif (!empty($row['user_id'])) {
                $pseudo = 'Utilisateur supprimé';
            }

            return [
                'id' => (int) ($row['id'] ?? 0),
                'user' => [
                    'id' => $uid,
                    'pseudo' => $pseudo,
                ],
                'message' => (string) ($row['message'] ?? ''),
                'createdAt' => (string) ($row['created_at'] ?? ''),
                'isRead' => ((int) ($row['is_read'] ?? 0)) === 1,
            ];
        }, $rows);

        return new JsonResponse($data);
    }

    #[Route('/equipe/{id}/send', name: 'app_chat_send', methods: ['POST'])]
    public function sendMessage(
        int $id,
        Request $request,
        EquipeRepository $equipeRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        try {
            $equipe = $equipeRepository->find($id);
            
            if (!$equipe) {
                return new JsonResponse(['error' => 'Équipe non trouvée (ID: '.$id.')'], 404);
            }

            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Non authentifié. Veuillez vous reconnecter.'], 401);
            }

            $content = $request->getContent();
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'JSON invalide'], 400);
            }

            $messageText = $data['message'] ?? '';

            if (empty(trim($messageText))) {
                return new JsonResponse(['error' => 'Le message ne peut pas être vide'], 400);
            }

            $chatMessage = new ChatMessage();
            $chatMessage->setUser($user);
            $chatMessage->setEquipe($equipe);
            $chatMessage->setMessage($messageText);
            $chatMessage->setCreatedAt(new \DateTime());

            $entityManager->persist($chatMessage);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => [
                    'id' => $chatMessage->getId(),
                    'user' => [
                        'id' => $user->getId(),
                        'pseudo' => method_exists($user, 'getPseudo') ? $user->getPseudo() : (method_exists($user, 'getNom') ? $user->getNom() : 'Utilisateur'),
                    ],
                    'message' => $chatMessage->getMessage(),
                    'createdAt' => $chatMessage->getCreatedAt()->format('Y-m-d H:i:s'),
                    'isRead' => $chatMessage->isRead(),
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur serveur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/equipe/{id}/ai-ask', name: 'app_chat_ai_ask', methods: ['POST'])]
    public function aiAsk(
        int $id,
        Request $request,
        EquipeRepository $equipeRepository,
        EntityManagerInterface $entityManager,
        \App\Service\TeamAnalystService $aiService
    ): JsonResponse {
        try {
            $equipe = $equipeRepository->find($id);
            if (!$equipe) {
                return new JsonResponse(['error' => 'Équipe non trouvée'], 404);
            }

            $user = $this->getUser();
            if (!$user) {
                return new JsonResponse(['error' => 'Non authentifié'], 401);
            }

            $data = json_decode($request->getContent(), true);
            $question = $data['question'] ?? null;

            $aiResponse = $aiService->analyzeTeamForUser($equipe, $question);

            if (!$aiResponse) {
                return new JsonResponse(['error' => 'L\'IA n\'a pas pu répondre'], 500);
            }

            // Save AI response as a message
            $chatMessage = new ChatMessage();
            $chatMessage->setUser(null); // System/AI message
            $chatMessage->setEquipe($equipe);
            $chatMessage->setMessage($aiResponse);
            $chatMessage->setCreatedAt(new \DateTime());

            $entityManager->persist($chatMessage);
            $entityManager->flush();

            return new JsonResponse([
                'success' => true,
                'message' => [
                    'id' => $chatMessage->getId(),
                    'user' => [
                        'id' => 0,
                        'pseudo' => 'Esportify AI Analyst',
                    ],
                    'message' => $chatMessage->getMessage(),
                    'createdAt' => $chatMessage->getCreatedAt()->format('Y-m-d H:i:s'),
                    'isRead' => false,
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur: ' . $e->getMessage()], 500);
        }
    }

    #[Route('/equipe/{id}/mark-read', name: 'app_chat_mark_read', methods: ['POST'])]
    public function markAsRead(
        int $id,
        EquipeRepository $equipeRepository,
        ChatMessageRepository $chatRepository
    ): JsonResponse {
        $equipe = $equipeRepository->find($id);
        
        if (!$equipe) {
            return new JsonResponse(['error' => 'Équipe non trouvée'], 404);
        }

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $chatRepository->markAllAsRead($equipe);

        return new JsonResponse(['success' => true]);
    }
}
