<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'user_home')]
    public function userHome(): Response
    {
        // Redirect to the public tournoi listing (front-office)
        return $this->redirectToRoute('tournoi_index');
    }
}
