<?php

namespace App\Controller\Backoffice;

use App\Entity\Produit;
use App\Repository\ProduitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/product')]
class ProductController extends AbstractController
{
    #[Route('/', name: 'admin_product_index', methods: ['GET'])]
    public function index(ProduitRepository $produitRepository): Response
    {
        return $this->render('backoffice/product/index.html.twig', [
            'produits' => $produitRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_product_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Simple manual form handling for brevity, normally usage of FormType is recommended
        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom'));
            $prix = (int) $request->request->get('prix');
            $stock = (int) $request->request->get('stock');
            $description = trim((string) $request->request->get('description'));
            $image = trim((string) $request->request->get('image'));
            $categorie = trim((string) $request->request->get('categorie'));

            $errors = [];
            if ($nom === '' || strlen($nom) > 255) {
                $errors[] = 'Nom invalide.';
            }
            if ($prix <= 0) {
                $errors[] = 'Prix invalide.';
            }
            if ($stock < 0) {
                $errors[] = 'Stock invalide.';
            }
            if ($description === '' || strlen($description) > 1000) {
                $errors[] = 'Description invalide.';
            }
            if ($image === '' || strlen($image) > 255 || !filter_var($image, FILTER_VALIDATE_URL)) {
                $errors[] = 'Image invalide (URL).';
            }
            if ($categorie === '' || strlen($categorie) > 255) {
                $errors[] = 'Categorie invalide.';
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('backoffice/product/new.html.twig');
            }

            $produit = new Produit();
            $produit->setNom($nom);
            $produit->setPrix($prix);
            $produit->setStock($stock);
            $produit->setDescription($description);
            $produit->setImage($image);
            $produit->setCategorie($categorie);
            $produit->setActive($request->request->has('active'));
            
            $entityManager->persist($produit);
            $entityManager->flush();

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('backoffice/product/new.html.twig');
    }

    #[Route('/{id}/edit', name: 'admin_product_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $nom = trim((string) $request->request->get('nom'));
            $prix = (int) $request->request->get('prix');
            $stock = (int) $request->request->get('stock');
            $description = trim((string) $request->request->get('description'));
            $image = trim((string) $request->request->get('image'));
            $categorie = trim((string) $request->request->get('categorie'));

            $errors = [];
            if ($nom === '' || strlen($nom) > 255) {
                $errors[] = 'Nom invalide.';
            }
            if ($prix <= 0) {
                $errors[] = 'Prix invalide.';
            }
            if ($stock < 0) {
                $errors[] = 'Stock invalide.';
            }
            if ($description === '' || strlen($description) > 1000) {
                $errors[] = 'Description invalide.';
            }
            if ($image === '' || strlen($image) > 255 || !filter_var($image, FILTER_VALIDATE_URL)) {
                $errors[] = 'Image invalide (URL).';
            }
            if ($categorie === '' || strlen($categorie) > 255) {
                $errors[] = 'Categorie invalide.';
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
                return $this->render('backoffice/product/edit.html.twig', [
                    'produit' => $produit,
                ]);
            }

            $produit->setNom($nom);
            $produit->setPrix($prix);
            $produit->setStock($stock);
            $produit->setDescription($description);
            $produit->setImage($image);
            $produit->setCategorie($categorie);
            $produit->setActive($request->request->has('active'));

            $entityManager->flush();

            return $this->redirectToRoute('admin_product_index');
        }

        return $this->render('backoffice/product/edit.html.twig', [
            'produit' => $produit,
        ]);
    }
    #[Route('/{id}/delete', name: 'admin_product_delete', methods: ['POST'])]
    public function delete(Request $request, Produit $produit, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produit->getId(), $request->request->get('_token'))) {
            $entityManager->remove($produit);
            $entityManager->flush();
        }

        return $this->redirectToRoute('admin_product_index');
    }
}
