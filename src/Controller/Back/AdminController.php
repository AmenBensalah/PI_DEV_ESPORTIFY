<?php

namespace App\Controller\Back;

use App\Entity\Produit;
use App\Form\ProduitType;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(ProduitRepository $produitRepository, \App\Repository\CategorieRepository $categorieRepository): Response
    {
        $statsGrouped = $produitRepository->countByCategory();
        $totalProducts = $produitRepository->count([]);
        $totalCategories = $categorieRepository->count([]);
        
        // Calculate low stock (e.g., < 5)
        $lowStockCount = $produitRepository->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.stock < 5')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('back/dashboard.html.twig', [
            'statsGrouped' => $statsGrouped,
            'totalProducts' => $totalProducts,
            'totalCategories' => $totalCategories,
            'lowStockCount' => $lowStockCount,
        ]);
    }

    #[Route('/produits', name: 'app_admin_produit_index', methods: ['GET'])]
    public function index(Request $request, ProduitRepository $produitRepository): Response
    {
        $search = $request->query->get('q');
        $sort = $request->query->get('sort', 'p.id');
        $direction = $request->query->get('dir', 'ASC');

        return $this->render('back/produit/index.html.twig', [
            'produits' => $produitRepository->searchBack($search, $sort, $direction),
            'current_search' => $search,
            'current_sort' => $sort,
            'current_dir' => $direction,
        ]);
    }

    #[Route('/produits/new', name: 'app_admin_produit_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        $produit = new Produit();
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // VALIDATION PERSONNALISÉE CÔTÉ SERVEUR
            
            // 1. Vérifier que le prix n'est pas trop bas
            if ($produit->getPrix() < 0.01) {
                $this->addFlash('error', 'Le prix doit être au minimum 0.01€');
                return $this->render('back/produit/new.html.twig', [
                    'produit' => $produit,
                    'form' => $form,
                ]);
            }
            
            // 2. Vérifier que le stock est cohérent
            if ($produit->getStock() < 0) {
                $this->addFlash('error', 'Le stock ne peut pas être négatif');
                return $this->render('back/produit/new.html.twig', [
                    'produit' => $produit,
                    'form' => $form,
                ]);
            }
            
            // 3. Si stock est 0, mettre automatiquement en rupture
            if ($produit->getStock() === 0 && $produit->getStatut() === 'disponible') {
                $produit->setStatut('rupture');
                $this->addFlash('warning', 'Le produit a été mis en rupture de stock automatiquement car le stock est à 0');
            }
            
            // 4. Vérifier que la catégorie existe bien
            if (!$produit->getCategorie()) {
                $this->addFlash('error', 'Vous devez sélectionner une catégorie');
                return $this->render('back/produit/new.html.twig', [
                    'produit' => $produit,
                    'form' => $form,
                ]);
            }

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Vérifier la taille du fichier (max 5MB)
                if ($imageFile->getSize() > 5242880) {
                    $this->addFlash('error', 'L\'image ne doit pas dépasser 5 MB');
                    return $this->render('back/produit/new.html.twig', [
                        'produit' => $produit,
                        'form' => $form,
                    ]);
                }
                
                // Vérifier le type MIME
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
                    $this->addFlash('error', 'Le fichier doit être une image (JPEG, PNG, GIF ou WebP)');
                    return $this->render('back/produit/new.html.twig', [
                        'produit' => $produit,
                        'form' => $form,
                    ]);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                        $newFilename
                    );
                    $produit->setImage('uploads/images/' . $newFilename);
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->render('back/produit/new.html.twig', [
                        'produit' => $produit,
                        'form' => $form,
                    ]);
                }
            }

            $entityManager->persist($produit);
            $entityManager->flush();
            
            $this->addFlash('success', 'Le produit "' . $produit->getNom() . '" a été créé avec succès !');

            return $this->redirectToRoute('app_admin_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/produit/new.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/produits/{id}', name: 'app_admin_produit_show', methods: ['GET'])]
    public function show(Produit $produit): Response
    {
        return $this->render('back/produit/show.html.twig', [
            'produit' => $produit,
        ]);
    }

    #[Route('/produits/{id}/edit', name: 'app_admin_produit_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager, \Symfony\Component\String\Slugger\SluggerInterface $slugger): Response
    {
        $form = $this->createForm(ProduitType::class, $produit);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // VALIDATION PERSONNALISÉE CÔTÉ SERVEUR
            
            // 1. Vérifier que le prix n'est pas trop bas
            if ($produit->getPrix() < 0.01) {
                $this->addFlash('error', 'Le prix doit être au minimum 0.01€');
                return $this->render('back/produit/edit.html.twig', [
                    'produit' => $produit,
                    'form' => $form,
                ]);
            }
            
            // 2. Vérifier que le stock est cohérent
            if ($produit->getStock() < 0) {
                $this->addFlash('error', 'Le stock ne peut pas être négatif');
                return $this->render('back/produit/edit.html.twig', [
                    'produit' => $produit,
                    'form' => $form,
                ]);
            }
            
            // 3. Si stock est 0, mettre automatiquement en rupture
            if ($produit->getStock() === 0 && $produit->getStatut() === 'disponible') {
                $produit->setStatut('rupture');
                $this->addFlash('warning', 'Le produit a été mis en rupture de stock automatiquement car le stock est à 0');
            }
            
            // 4. Si stock > 0 et statut rupture, proposer de remettre disponible
            if ($produit->getStock() > 0 && $produit->getStatut() === 'rupture') {
                $this->addFlash('info', 'Le stock est maintenant disponible. Vous pouvez changer le statut à "disponible" si nécessaire.');
            }

             /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                // Vérifier la taille du fichier (max 5MB)
                if ($imageFile->getSize() > 5242880) {
                    $this->addFlash('error', 'L\'image ne doit pas dépasser 5 MB');
                    return $this->render('back/produit/edit.html.twig', [
                        'produit' => $produit,
                        'form' => $form,
                    ]);
                }
                
                // Vérifier le type MIME
                $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($imageFile->getMimeType(), $allowedMimes)) {
                    $this->addFlash('error', 'Le fichier doit être une image (JPEG, PNG, GIF ou WebP)');
                    return $this->render('back/produit/edit.html.twig', [
                        'produit' => $produit,
                        'form' => $form,
                    ]);
                }
                
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/images',
                        $newFilename
                    );
                    $produit->setImage('uploads/images/' . $newFilename);
                } catch (\Symfony\Component\HttpFoundation\File\Exception\FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage());
                    return $this->render('back/produit/edit.html.twig', [
                        'produit' => $produit,
                        'form' => $form,
                    ]);
                }
            }
            
            $entityManager->flush();
            
            $this->addFlash('success', 'Le produit "' . $produit->getNom() . '" a été modifié avec succès !');

            return $this->redirectToRoute('app_admin_produit_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('back/produit/edit.html.twig', [
            'produit' => $produit,
            'form' => $form,
        ]);
    }

    #[Route('/produits/{id}', name: 'app_admin_produit_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_produit_index', [], Response::HTTP_SEE_OTHER);
    }
}
