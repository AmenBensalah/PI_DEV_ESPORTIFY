<?php

namespace App\Controller\Backoffice;

use App\Repository\CommandeRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProduitRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard', methods: ['GET'])]
    public function index(
        CommandeRepository $commandeRepository,
        ProduitRepository $produitRepository,
        PaymentRepository $paymentRepository
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'orderCount' => $commandeRepository->count([]),
            'productCount' => $produitRepository->count([]),
            'paymentCount' => $paymentRepository->count([]),
        ]);
    }
}
