<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SecurityController extends AbstractController
{
    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        // Fallback logout route for projects without Symfony security logout configured.
        return $this->redirectToRoute('front_product_list');
    }
}
