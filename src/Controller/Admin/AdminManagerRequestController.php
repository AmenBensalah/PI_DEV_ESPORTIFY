<?php

namespace App\Controller\Admin;

use App\Entity\ManagerRequest;
use App\Enum\Role;
use App\Repository\ManagerRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/requests')]
#[IsGranted('ROLE_ADMIN')]
class AdminManagerRequestController extends AbstractController
{
    #[Route('/', name: 'admin_manager_requests_index', methods: ['GET'])]
    public function index(Request $request, ManagerRequestRepository $managerRequestRepository): Response
    {
        $query = $request->query->get('q');
        $status = $request->query->get('status', 'pending');
        $sort = $request->query->get('sort', 'id');
        $direction = $request->query->get('direction', 'DESC');

        return $this->render('admin/manager_requests/index.html.twig', [
            'manager_requests' => $managerRequestRepository->searchAndSort($query, $status, $sort, $direction),
            'currentQuery' => $query,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentDirection' => $direction
        ]);
    }

    #[Route('/{id}/accept', name: 'admin_manager_requests_accept', methods: ['POST'])]
    public function accept(Request $request, ManagerRequest $managerRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('accept'.$managerRequest->getId(), $request->request->get('_token'))) {
            // Update request status only, do NOT change user role automatically
            $managerRequest->setStatus('accepted');
            $entityManager->flush();

            $this->addFlash('success', 'La demande a été marquée comme acceptée. Vous pouvez modifier le rôle de l\'utilisateur manuellement si nécessaire.');
        }

        return $this->redirectToRoute('admin_manager_requests_index');
    }

    #[Route('/{id}', name: 'admin_manager_requests_delete', methods: ['POST'])]
    public function delete(Request $request, ManagerRequest $managerRequest, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$managerRequest->getId(), $request->request->get('_token'))) {
            $entityManager->remove($managerRequest);
            $entityManager->flush();
            $this->addFlash('success', 'La demande a été supprimée.');
        }

        return $this->redirectToRoute('admin_manager_requests_index');
    }
}
