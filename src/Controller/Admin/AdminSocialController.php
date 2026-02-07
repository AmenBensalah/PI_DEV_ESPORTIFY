<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/social')]
#[IsGranted('ROLE_ADMIN')]
class AdminSocialController extends AbstractController
{
    #[Route('/', name: 'admin_social', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('admin/social.html.twig');
    }
}
