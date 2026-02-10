<?php

namespace App\Controller\Admin;

use App\Entity\Commentaire;
use App\Repository\CommentaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/fil/admin/comments')]
class AdminCommentController extends AbstractController
{
    #[Route('/', name: 'fil_admin_comment_index')]
    public function index(CommentaireRepository $commentaireRepository): Response
    {
        return $this->render('admin/comment/index.html.twig', [
            'commentaires' => $commentaireRepository->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'fil_admin_comment_delete', methods: ['POST'])]
    public function delete(Commentaire $commentaire, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete_comment_' . $commentaire->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('fil_admin_comment_index');
    }
}
