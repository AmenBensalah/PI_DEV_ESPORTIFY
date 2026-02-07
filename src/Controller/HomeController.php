<?php

namespace App\Controller;

use App\Repository\AnnouncementRepository;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(PostRepository $postRepository, AnnouncementRepository $announcementRepository): Response
    {
        return $this->render('home/index.html.twig', [
            'posts' => $postRepository->findBy([], ['createdAt' => 'DESC']),
            'announcements' => $announcementRepository->findBy([], ['createdAt' => 'DESC'], 6),
        ]);
    }
}

