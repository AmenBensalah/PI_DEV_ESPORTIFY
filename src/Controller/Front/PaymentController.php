<?php

namespace App\Controller\Front;

use App\Entity\Commande;
use App\Entity\Payment;
use App\Service\PaymentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/payment')]
class PaymentController extends AbstractController
{
    #[Route('/pay/{id}', name: 'front_payment_init', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        if ($commande->getStatut() !== 'pending_payment') {
            $this->addFlash('error', 'Cette commande n\'est pas prete pour le paiement.');
            return $this->redirectToRoute('front_order_cart');
        }

        return $this->render('front/payment/pay.html.twig', [
            'commande' => $commande
        ]);
    }

    #[Route('/process/{id}', name: 'front_payment_process', methods: ['POST'])]
    public function process(
        Commande $commande,
        PaymentService $paymentService,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        if ($commande->getStatut() !== 'pending_payment') {
            $this->addFlash('error', 'Cette commande n\'est pas prete pour le paiement.');
            return $this->redirectToRoute('front_order_cart');
        }

        try {
            $successUrl = $urlGenerator->generate(
                'front_payment_success',
                ['id' => $commande->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $successUrl .= '?session_id={CHECKOUT_SESSION_ID}';

            $cancelUrl = $urlGenerator->generate(
                'front_payment_cancel',
                ['id' => $commande->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $session = $paymentService->createCheckoutSession($commande, $successUrl, $cancelUrl);

            if (!$session->url) {
                throw new \Exception('Stripe n\'a pas retourne d\'URL de session.');
            }

            return new RedirectResponse($session->url, 303);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('front_payment_init', ['id' => $commande->getId()]);
        }
    }

    #[Route('/success/{id}', name: 'front_payment_success', methods: ['GET'])]
    public function success(
        Commande $commande,
        Request $request,
        PaymentService $paymentService,
        SessionInterface $session
    ): Response {
        $sessionId = (string) $request->query->get('session_id', '');
        if ($sessionId === '') {
            $this->addFlash('error', 'Session de paiement introuvable.');
            return $this->redirectToRoute('front_order_cart');
        }

        try {
            $payment = $paymentService->confirmPayment($commande, $sessionId);
            $history = $session->get('order_history_ids', []);
            $orderId = $commande->getId();
            if ($orderId && !in_array($orderId, $history, true)) {
                $history[] = $orderId;
                $session->set('order_history_ids', $history);
            }
            return $this->redirectToRoute('front_payment_result', [
                'id' => $payment->getId()
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('front_payment_init', ['id' => $commande->getId()]);
        }
    }

    #[Route('/cancel/{id}', name: 'front_payment_cancel', methods: ['GET'])]
    public function cancel(): Response
    {
        $this->addFlash('error', 'Paiement annule.');
        return $this->redirectToRoute('front_order_cart');
    }

    #[Route('/result/{id}', name: 'front_payment_result', methods: ['GET'])]
    public function result(Payment $payment): Response
    {
        return $this->render('front/payment/result.html.twig', [
            'payment' => $payment,
            'commande' => $payment->getCommande(),
            'success' => true
        ]);
    }
}
