<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Repository\ChatMessageRepository;
use App\Repository\EquipeRepository;
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
        ChatMessageRepository $chatRepository
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

        $messages = $chatRepository->findRecentByEquipe($equipe, 100);
        
        $data = array_map(function($message) {
            return [
                'id' => $message->getId(),
                'user' => [
                    'id' => $message->getUser() ? $message->getUser()->getId() : 0,
                    'pseudo' => $message->getUser() 
                        ? ($message->getUser()->getPseudo() ?? $message->getUser()->getNom())
                        : 'Esportify AI Analyst',
                ],
                'message' => $message->getMessage(),
                'createdAt' => $message->getCreatedAt()->format('Y-m-d H:i:s'),
                'isRead' => $message->isRead(),
            ];
        }, array_reverse($messages));

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
