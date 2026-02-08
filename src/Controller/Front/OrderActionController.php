<?php

namespace App\Controller\Front;

use App\Entity\Commande;
use App\Entity\Produit;
use App\Repository\CommandeRepository;
use App\Repository\ProduitRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/order')]
class OrderActionController extends AbstractController
{
    #[Route('/', name: 'front_order_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('front_order_cart');
    }
    #[Route('/create', name: 'front_order_create', methods: ['POST'])]
    public function create(OrderService $orderService, SessionInterface $session): Response
    {
        $commande = $orderService->createOrder();
        $session->set('current_order_id', $commande->getId());
        
        return $this->redirectToRoute('front_order_cart');
    }

    #[Route('/add-product/{id}', name: 'front_order_add_product', methods: ['GET', 'POST'], defaults: ['id' => null])]
    public function addProduct(?Produit $produit, Request $request, OrderService $orderService, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();
        $debugLog = __DIR__ . '/../../../../debug_cart.log';
        $logData = sprintf(
            "[%s] addProduct: SessionID=%s, ProductID=%s, ExistingOrderID=%s\n",
            date('Y-m-d H:i:s'),
            $session->getId(),
            $produit ? $produit->getId() : 'NULL',
            $session->get('current_order_id', 'NULL')
        );
        file_put_contents($debugLog, $logData, FILE_APPEND);

        if (!$produit) {
             $this->addFlash('error', 'Produit non trouvé.');
             return $this->redirectToRoute('app_front_produit_index');
        }
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('app_front_produit_index');
        }

        $orderId = $session->get('current_order_id');
        $commande = null;

        if ($orderId) {
             $commande = $entityManager->getRepository(Commande::class)->find($orderId);
             file_put_contents($debugLog, sprintf("  -> Found existing order in DB: %s\n", $commande ? 'YES' : 'NO'), FILE_APPEND);
        }

        if (!$commande) {
            $commande = $orderService->createOrder();
            // Force flush and check ID immediately
            $entityManager->flush();
            $session->set('current_order_id', $commande->getId());
            file_put_contents($debugLog, sprintf("  -> Created NEW order: %d (ID from Entity: %s)\n", $commande->getId(), $commande->getId()), FILE_APPEND);
        }
        
        $quantite = (int) $request->request->get('quantite', 1);
        if ($quantite <= 0 || $quantite > 100) {
            $this->addFlash('error', 'Quantite invalide.');
            return $this->redirectToRoute('app_front_produit_index');
        }
        
        try {
            $orderService->addProductToOrder($commande, $produit, $quantite);
            // Redundant flush just to be safe and catch errors here
            $entityManager->flush();
            
            $this->addFlash('success', 'Produit ajouté.');
            file_put_contents($debugLog, sprintf("  -> Product added. Line items count: %d\n", $commande->getLignesCommande()->count()), FILE_APPEND);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            file_put_contents($debugLog, sprintf("  -> ERROR adding product: %s\n", $e->getMessage()), FILE_APPEND);
        }

        return $this->redirectToRoute('front_order_cart'); // Redirect to Cart by default to show it works
    }

    #[Route('/cart', name: 'front_order_cart', methods: ['GET'])]
    public function cart(Request $request, CommandeRepository $commandeRepository): Response
    {
        $session = $request->getSession();
        $orderId = $session->get('current_order_id');
        
        $debugLog = __DIR__ . '/../../../../debug_cart.log';
        file_put_contents($debugLog, sprintf(
            "[%s] cart: SessionID=%s, OrderIDInSession=%s\n",
            date('Y-m-d H:i:s'),
            $session->getId(),
            $orderId ?: 'NULL'
        ), FILE_APPEND);

        $commande = null;
        if ($orderId) {
             $commande = $commandeRepository->find($orderId);
             file_put_contents($debugLog, sprintf("  -> Order found in DB: %s\n", $commande ? 'YES' : 'NO'), FILE_APPEND);
        } else {
             file_put_contents($debugLog, "  -> No order ID in session.\n", FILE_APPEND);
        }

        $totalQuantite = 0;
        if ($commande) {
            foreach ($commande->getLignesCommande() as $ligne) {
                $totalQuantite += $ligne->getQuantite();
            }
        }

        return $this->render('front/order/cart.html.twig', [
            'commande' => $commande,
            'total_quantite' => $totalQuantite,
        ]);
    }

    #[Route('/panier', name: 'front_order_cart_details', methods: ['GET'])]
    public function cartDetails(Request $request, CommandeRepository $commandeRepository): Response
    {
        $session = $request->getSession();
        $orderId = $session->get('current_order_id');

        $commande = null;
        if ($orderId) {
            $commande = $commandeRepository->find($orderId);
        }

        $totalQuantite = 0;
        if ($commande) {
            foreach ($commande->getLignesCommande() as $ligne) {
                $totalQuantite += $ligne->getQuantite();
            }
        }

        return $this->render('front/order/cart_details.html.twig', [
            'commande' => $commande,
            'total_quantite' => $totalQuantite,
        ]);
    }

    #[Route('/step-1', name: 'front_order_step1', methods: ['GET', 'POST'])]
    public function step1(SessionInterface $session, Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $entityManager): Response
    {
        $orderId = $session->get('current_order_id');
        if (!$orderId) {
            $this->addFlash('error', 'Aucune commande en cours.');
            return $this->redirectToRoute('app_front_produit_index');
        }

        $commande = $commandeRepository->find($orderId);
        
        if (!$commande) {
            $this->addFlash('error', 'Commande introuvable.');
            $session->remove('current_order_id');
            return $this->redirectToRoute('app_front_produit_index');
        }

        if ($commande->getStatut() !== 'draft') {
            if ($commande->getStatut() === 'pending_payment') {
                return $this->redirectToRoute('front_payment_init', ['id' => $commande->getId()]);
            }

            $this->addFlash('error', 'Cette commande ne peut plus etre modifiee.');
            return $this->redirectToRoute('front_order_cart');
        }

        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('front_order_cart');
        }

        $nom = trim((string) $request->request->get('nom', ''));
        $prenom = trim((string) $request->request->get('prenom', ''));
        $quantite = (int) $request->request->get('quantite', 0);
        $numtelRaw = preg_replace('/\D+/', '', (string) $request->request->get('numtel', ''));
        $numtel = (int) $numtelRaw;

        $errors = [];
        if ($nom === '' || strlen($nom) > 255) {
            $errors[] = 'Nom invalide.';
        }
        if ($prenom === '' || strlen($prenom) > 255) {
            $errors[] = 'Prenom invalide.';
        }
        if ($quantite <= 0) {
            $errors[] = 'Quantite invalide.';
        }
        if ($numtelRaw === '' || $numtel <= 0 || strlen($numtelRaw) < 8 || strlen($numtelRaw) > 15) {
            $errors[] = 'Telephone invalide.';
        }

        if ($errors) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('front_order_cart');
        }

        try {
            $commande->setNom($nom);
            $commande->setPrenom($prenom);
            $commande->setQuantite($quantite);
            $commande->setNumtel($numtel);

            $entityManager->flush();
            return $this->redirectToRoute('front_order_step2');
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('front_order_cart');
        }
    }

    #[Route('/step-2', name: 'front_order_step2', methods: ['GET', 'POST'])]
    public function step2(OrderService $orderService, SessionInterface $session, Request $request, CommandeRepository $commandeRepository, EntityManagerInterface $entityManager): Response
    {
        $orderId = $session->get('current_order_id');
        if (!$orderId) {
            $this->addFlash('error', 'Aucune commande en cours.');
            return $this->redirectToRoute('app_front_produit_index');
        }

        $commande = $commandeRepository->find($orderId);
        if (!$commande) {
            $this->addFlash('error', 'Commande introuvable.');
            $session->remove('current_order_id');
            return $this->redirectToRoute('app_front_produit_index');
        }

        if ($commande->getStatut() !== 'draft') {
            if ($commande->getStatut() === 'pending_payment') {
                return $this->redirectToRoute('front_payment_init', ['id' => $commande->getId()]);
            }
            $this->addFlash('error', 'Cette commande ne peut plus etre modifiee.');
            return $this->redirectToRoute('front_order_cart');
        }

        if ($request->isMethod('GET')) {
            return $this->render('front/order/address.html.twig', [
                'commande' => $commande,
            ]);
        }

        $pays = trim((string) $request->request->get('pays', ''));
        $gouvernerat = trim((string) $request->request->get('gouvernerat', ''));
        $codePostal = trim((string) $request->request->get('code_postal', ''));
        $adresse = trim((string) $request->request->get('adresse', ''));
        $adresseDetail = trim((string) $request->request->get('adresse_detail', ''));

        $errors = [];
        if ($pays === '' || strlen($pays) > 255 || !preg_match('/^[\p{L}\s\-]+$/u', $pays)) {
            $errors[] = 'Pays invalide.';
        }
        if ($gouvernerat === '' || strlen($gouvernerat) > 255 || !preg_match('/^[\p{L}\s\-]+$/u', $gouvernerat)) {
            $errors[] = 'Gouvernerat invalide.';
        }
        if ($codePostal === '' || strlen($codePostal) > 10 || !preg_match('/^[0-9]+$/', $codePostal)) {
            $errors[] = 'Code postal invalide.';
        }
        if ($adresse === '' || strlen($adresse) > 255) {
            $errors[] = 'Adresse invalide.';
        }
        if ($adresseDetail === '' || strlen($adresseDetail) > 500) {
            $errors[] = 'Adresse exacte invalide.';
        }

        if ($errors) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
            return $this->redirectToRoute('front_order_step2');
        }

        try {
            $commande->setPays($pays);
            $commande->setGouvernerat($gouvernerat);
            $commande->setCodePostal($codePostal);
            $commande->setAdresse($adresse);
            $commande->setAdresseDetail($adresseDetail);

            $entityManager->flush();

            $orderService->confirmOrder($commande);
            return $this->redirectToRoute('front_payment_init', ['id' => $commande->getId()]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('front_order_step2');
        }
    }
    
    #[Route('/confirmed', name: 'front_order_show_confirmed', methods: ['GET'])]
    public function showConfirmed(SessionInterface $session, CommandeRepository $commandeRepository): Response
    {
        $orderId = $session->get('current_order_id');
        $commande = null;
        if ($orderId) {
             $commande = $commandeRepository->find($orderId);
        }

        return $this->render('front/order/confirm.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/history', name: 'front_order_history', methods: ['GET'])]
    public function history(SessionInterface $session, CommandeRepository $commandeRepository): Response
    {
        $ids = $session->get('order_history_ids', []);
        $commandes = [];
        if ($ids) {
            $commandes = $commandeRepository->findBy(['id' => $ids], ['id' => 'DESC']);
        }

        return $this->render('front/order/history.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    #[Route('/my/{id}', name: 'front_order_show', methods: ['GET'])]
    public function showMy(Commande $commande, SessionInterface $session): Response
    {
        $ids = $session->get('order_history_ids', []);
        if (!in_array($commande->getId(), $ids, true)) {
            $this->addFlash('error', 'Commande non disponible.');
            return $this->redirectToRoute('front_order_history');
        }

        return $this->render('front/order/show.html.twig', [
            'commande' => $commande,
        ]);
    }
    #[Route('/cancel', name: 'front_order_cancel', methods: ['POST'])]
    public function cancel(OrderService $orderService, SessionInterface $session, CommandeRepository $commandeRepository): Response
    {
        $orderId = $session->get('current_order_id');
        if ($orderId) {
            $commande = $commandeRepository->find($orderId);
            
            if ($commande) {
                $orderService->cancelOrder($commande);
                $session->remove('current_order_id');
                $this->addFlash('success', 'Commande annulée.');
            }
        }

        return $this->redirectToRoute('app_front_produit_index');
    }
}
