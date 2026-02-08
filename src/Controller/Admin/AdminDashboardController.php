<?php

namespace App\Controller\Admin;

use App\Repository\AnnouncementRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminDashboardController extends AbstractController
{
    #[Route('/fil/admin', name: 'fil_admin_dashboard')]
    public function index(PostRepository $postRepository, AnnouncementRepository $announcementRepository): Response
    {
        return $this->render('admin/dashboard.html.twig', [
            'postCount' => $postRepository->count([]),
            'announcementCount' => $announcementRepository->count([]),
        ]);
    }
}
