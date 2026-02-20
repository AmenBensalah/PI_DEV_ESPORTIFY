<?php

namespace App\Controller\Backoffice;

use App\Entity\Payment;
use App\Repository\PaymentRepository;
use App\Service\RevenueForecastService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/payment')]
class PaymentController extends AbstractController
{
    #[Route('/', name: 'admin_payment_index', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        RevenueForecastService $revenueForecastService,
        Request $request
    ): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));
        $sort = trim((string) $request->query->get('sort', 'id'));
        $direction = strtoupper(trim((string) $request->query->get('direction', 'DESC'))) === 'ASC' ? 'ASC' : 'DESC';

        $payments = $paymentRepository->searchAndSort($query, $status, $sort, $direction);

        if ($request->isXmlHttpRequest() || $request->query->getBoolean('ajax')) {
            return $this->render('backoffice/payment/_table.html.twig', [
                'payments' => $payments,
            ]);
        }

        $analytics = $revenueForecastService->buildPaymentForecastDashboard();

        return $this->render('backoffice/payment/index.html.twig', [
            'payments' => $payments,
            'analytics' => $analytics,
            'currentQuery' => $query,
            'currentStatus' => $status,
            'currentSort' => $sort,
            'currentDirection' => $direction,
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
