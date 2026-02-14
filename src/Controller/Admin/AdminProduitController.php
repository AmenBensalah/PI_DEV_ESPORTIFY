<?php

namespace App\Controller\Admin;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/boutique/produits')]
#[IsGranted('ROLE_ADMIN')]
class AdminProduitController extends AbstractController
{
    #[Route('/', name: 'admin_boutique_produit_index', methods: ['GET', 'POST'])]
    public function index(Request $request, ProduitRepository $repository, \App\Repository\CategorieRepository $catRepo): Response
    {
        // 1. Get Filters
        $filters = [
            'q' => $request->query->get('q'),
            'minPrice' => $request->query->get('minPrice'),
            'maxPrice' => $request->query->get('maxPrice'),
            'categorie' => $request->query->get('categorie'),
            'statut' => $request->query->get('statut'),
            'sort' => $request->query->get('sort'),
        ];

        // 2. Search
        $produits = $repository->searchBack($filters);

        // Check for AJAX
        if ($request->isXmlHttpRequest() || $request->query->get('ajax')) {
            return $this->render('admin/boutique/produit/_table.html.twig', [
                'produits' => $produits,
            ]);
        }

        // 3. Calculate Total Value
        $totalValue = 0;
        foreach ($produits as $p) {
            $totalValue += $p->getPrix() * $p->getStock();
        }

        // 4. Render (Pass both camelCase and snake_case for compatibility)
        return $this->render('admin/boutique/produit/index.html.twig', [
            'produits' => $produits,
            'categories' => $catRepo->findAll(),
            'currentSearch' => $filters['q'],
            'current_search' => $filters['q'], // Compatibility
            'minPrice' => $filters['minPrice'],
            'maxPrice' => $filters['maxPrice'],
            'currentCat' => $filters['categorie'],
            'currentStatut' => $filters['statut'],
            'currentSort' => $filters['sort'],
            'current_sort' => $filters['sort'], // Compatibility
            'current_dir' => 'ASC', // Default for template compatibility
            'totalValue' => number_format($totalValue, 2)
        ]);
    }

    #[Route('/new', name: 'admin_boutique_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $produit, $slugger);
            $em->persist($produit);
            $em->flush();
            $this->addFlash('success', 'Produit créé.');
            return $this->redirectToRoute('admin_boutique_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/boutique/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_boutique_produit_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('admin/boutique/produit/show.html.twig', ['produit' => $produit]);
    }

    #[Route('/{id}/edit', name: 'admin_boutique_produit_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handleImageUpload($form, $produit, $slugger);
            $em->flush();
            $this->addFlash('success', 'Produit mis à jour.');
            return $this->redirectToRoute('admin_boutique_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/boutique/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_boutique_produit_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete' . $produit->getId(), $token)) {
            $em->remove($produit);
            $em->flush();
            $this->addFlash('success', 'Produit supprimé.');
        }
        return $this->redirectToRoute('admin_boutique_produit_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handleImageUpload($form, Produit $produit, SluggerInterface $slugger): void
    {
        $file = $form->get('imageFile')->getData();
        if (!$file) {
            return;
        }
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        $dir = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $file->move($dir, $newFilename);
        $produit->setImage('uploads/images/' . $newFilename);
    }
}
