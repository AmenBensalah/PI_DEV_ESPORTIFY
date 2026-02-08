<?php

namespace App\Controller\Backoffice;

use App\Entity\Commande;
use App\Repository\CommandeRepository;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'admin_order_index', methods: ['GET'])]
    public function index(CommandeRepository $commandeRepository, Request $request): Response
    {
        $searchProduit = trim((string) $request->query->get('produit', ''));
        $commandesRecherchees = [];
        if ($searchProduit !== '') {
            $commandesRecherchees = $commandeRepository->findByProduitNomLike($searchProduit);
            if (!$commandesRecherchees) {
                $this->addFlash('error', 'Aucune commande pour ce produit.');
            }
        }

        return $this->render('backoffice/order/index.html.twig', [
            'commandes' => $commandeRepository->findAll(),
            'commandes_recherchees' => $commandesRecherchees,
            'search_produit' => $searchProduit,
            'commande_counts' => $commandeRepository->countByStatut(),
        ]);
    }

    #[Route('/{id}', name: 'admin_order_show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('backoffice/order/show.html.twig', [
            'commande' => $commande,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        $statuts = ['draft', 'pending_payment', 'paid', 'cancelled'];

        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom'));
            $prenom = trim((string) $request->request->get('prenom'));
            $adresse = trim((string) $request->request->get('adresse'));
            $quantite = (int) $request->request->get('quantite');
            $numtelRaw = preg_replace('/\D+/', '', (string) $request->request->get('numtel', ''));
            $numtel = (int) $numtelRaw;

            $errors = [];
            if ($nom === '' || strlen($nom) > 255) {
                $errors[] = 'Nom invalide.';
            }
            if ($prenom === '' || strlen($prenom) > 255) {
                $errors[] = 'Prenom invalide.';
            }
            if ($adresse === '' || strlen($adresse) > 255) {
                $errors[] = 'Adresse invalide.';
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
                return $this->render('backoffice/order/edit.html.twig', [
                    'commande' => $commande,
                    'statuts' => $statuts,
                ]);
            }

            $commande->setNom($nom);
            $commande->setPrenom($prenom);
            $commande->setAdresse($adresse);
            $commande->setQuantite($quantite);
            $commande->setNumtel($numtel);

            $statut = (string) $request->request->get('statut');
            if (in_array($statut, $statuts, true)) {
                $commande->setStatut($statut);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Commande mise a jour.');

            return $this->redirectToRoute('admin_order_show', ['id' => $commande->getId()]);
        }

        return $this->render('backoffice/order/edit.html.twig', [
            'commande' => $commande,
            'statuts' => $statuts,
        ]);
    }

    #[Route('/{id}/cancel', name: 'admin_order_cancel', methods: ['GET', 'POST'])]
    public function cancel(Commande $commande, OrderService $orderService, \Symfony\Component\HttpFoundation\Request $request): Response
    {
        if ($request->isMethod('GET')) {
            return $this->redirectToRoute('admin_order_index');
        }

        $orderService->cancelOrder($commande);
        return $this->redirectToRoute('admin_order_index');
    }

    #[Route('/{id}/delete', name: 'admin_order_delete', methods: ['POST'])]
    public function delete(Request $request, Commande $commande, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_order'.$commande->getId(), $request->request->get('_token'))) {
            $entityManager->remove($commande);
            $entityManager->flush();
            $this->addFlash('success', 'Commande supprimee.');
        }

        return $this->redirectToRoute('admin_order_index');
    }
}
