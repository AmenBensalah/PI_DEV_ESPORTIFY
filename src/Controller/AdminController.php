<?php

namespace App\Controller;

use App\Repository\EquipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(EquipeRepository $equipeRepository): Response
    {
        // Counts for the dashboard widgets
        $equipeCount = $equipeRepository->count([]);
        
        return $this->render('admin/index.html.twig', [
            'equipeCount' => $equipeCount,
            'userCount' => 1250, // Mock data
            'reportCount' => 15, // Mock data
        ]);
    }

    #[Route('/equipes', name: 'admin_equipes')]
    public function equipes(EquipeRepository $equipeRepository): Response
    {
        return $this->render('admin/equipes.html.twig', [
            'equipes' => $equipeRepository->findAll(),
        ]);
    }

    #[Route('/users', name: 'admin_users')]
    public function users(): Response
    {
        return $this->render('admin/users.html.twig');
    }

    #[Route('/settings', name: 'admin_settings')]
    public function settings(): Response
    {
        return $this->render('admin/settings.html.twig');
    }

    #[Route('/tournois', name: 'admin_tournois')]
    public function tournois(): Response
    {
        return $this->render('admin/tournois.html.twig');
    }

    #[Route('/boutique', name: 'admin_boutique')]
    public function boutique(): Response
    {
        return $this->render('admin/boutique.html.twig');
    }

    #[Route('/requests', name: 'admin_requests')]
    public function requests(): Response
    {
        return $this->render('admin/requests.html.twig');
    }

    #[Route('/social', name: 'admin_social')]
    public function social(): Response
    {
        return $this->render('admin/social.html.twig');
    }
}
