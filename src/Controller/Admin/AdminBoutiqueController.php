<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boutique')]
#[IsGranted('ROLE_ADMIN')]
class AdminBoutiqueController extends AbstractController
{
    #[Route('/', name: 'admin_boutique', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/boutique.html.twig');
    }
}
