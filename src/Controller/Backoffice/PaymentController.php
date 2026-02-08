<?php

namespace App\Controller\Backoffice;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'admin_payment_index', methods: ['GET'])]
    public function index(PaymentRepository $paymentRepository): Response
    {
        return $this-> render('backoffice/payment/index.html.twig', [
            'payments' => $paymentRepository->findAll(),
        ]);
    }
    #[Route('/{id}', name: 'admin_payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('backoffice/payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }
}
