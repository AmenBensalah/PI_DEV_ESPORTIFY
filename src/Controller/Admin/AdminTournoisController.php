<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/tournois')]
#[IsGranted('ROLE_ADMIN')]
class AdminTournoisController extends AbstractController
{
    #[Route('/', name: 'admin_tournois', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/tournois.html.twig');
    }
}
