<?php

namespace App\Controller\Admin;

use App\Entity\Equipe;
use App\Repository\EquipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/equipes')]
#[IsGranted('ROLE_ADMIN')]
class AdminEquipeController extends AbstractController
{
    #[Route('/', name: 'admin_equipes', methods: ['GET'])]
    public function index(Request $request, EquipeRepository $equipeRepository): Response
    {
        $query = $request->query->get('q');
        $region = $request->query->get('region');
        $visibility = $request->query->get('visibility');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');

        return $this->render('admin/equipes/index.html.twig', [
            'equipes' => $equipeRepository->searchAndSort($query, $region, $visibility, $sort, $direction),
            'currentQuery' => $query,
            'currentRegion' => $region,
            'currentVisibility' => $visibility,
            'currentSort' => $sort,
            'currentDirection' => $direction
        ]);
    }

    #[Route('/{id}', name: 'admin_equipes_delete', methods: ['POST'])]
    public function delete(Request $request, Equipe $equipe, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$equipe->getId(), $request->request->get('_token'))) {
            $entityManager->remove($equipe);
            $entityManager->flush();
            $this->addFlash('success', 'L\'équipe a été supprimée avec succès.');
        }

        return $this->redirectToRoute('admin_equipes');
    }
}
