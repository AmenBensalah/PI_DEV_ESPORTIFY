<?php

namespace App\Controller\Front;

use App\Repository\ProduitRepository;
use App\Repository\EquipeRepository;
use App\Service\GeminiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChatbotController extends AbstractController
{
    #[Route('/api/chatbot', name: 'api_chatbot', methods: ['POST'])]
    public function chat(
        Request $request, 
        GeminiService $geminiService,
        ProduitRepository $produitRepository,
        EquipeRepository $equipeRepository
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true);
            $message = $data['message'] ?? '';
            $history = $data['history'] ?? [];

            if (empty($message)) {
                return new JsonResponse(['error' => 'No message provided'], Response::HTTP_BAD_REQUEST);
            }

            // Build Shop context (Enhanced Metier Logic)
            $products = $produitRepository->findBy(['statut' => 'disponible']);
            $shopContext = "CONTEXTE BOUTIQUE (Catalogue Actuel) :\n";
            
            if (empty($products)) {
                $shopContext .= "Aucun produit disponible pour le moment.\n";
            } else {
                foreach ($products as $p) {
                    $stockInfo = $p->getStock() < 5 ? " (⚠️ Stock faible: " . $p->getStock() . ")" : "";
                    $shopContext .= "- " . $p->getNom() . " : " . $p->getPrix() . "€" . $stockInfo . "\n";
                }
            }

            // Add a "Featured" or "New" item logic if possible (Simulated Metier)
            $newestProduct = $produitRepository->findOneBy([], ['id' => 'DESC']);
            if ($newestProduct) {
                $shopContext .= "\nNOUVEAUTÉ À METTRE EN AVANT : " . $newestProduct->getNom() . " à " . $newestProduct->getPrix() . "€ !";
            }

            // Build Teams context
            $teams = $equipeRepository->findBy([], null, 5);
            $teamContext = "Équipes Vedettes :\n";
            foreach ($teams as $t) {
                $teamContext .= "- " . $t->getNomEquipe() . " (Rang: " . $t->getClassement() . ")\n";
            }

            $currentPage = $data['page'] ?? 'Inconnue';
            $currentUrl = $data['url'] ?? '';

            $fullContext = $shopContext . "\n" . $teamContext . "\nPage actuelle : " . $currentPage . " (" . $currentUrl . ")";
            
            $user = $this->getUser();
            $userName = 'Hacker Anonyme';
            if ($user) {
                $userName = $user->getPseudo() ?: ($user->getNom() ?: 'Joueur');
            }

            $botResponse = $geminiService->chat($message, $history, $fullContext, $userName);

            return new JsonResponse([
                'response' => $botResponse
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'response' => "Désolée, mon système interne rencontre une instabilité (Erreur: " . $e->getMessage() . ")"
            ]);
        }
    }
}
