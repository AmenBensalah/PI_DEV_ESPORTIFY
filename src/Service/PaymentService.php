<?php

namespace App\Service;

use App\Entity\Commande;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class PaymentService
{
    public function __construct(
        private OrderService $orderService,
        private EntityManagerInterface $entityManager,
        #[Autowire('%env(STRIPE_SECRET_KEY)%')] private string $stripeSecretKey
    ) {
    }

    public function createCheckoutSession(Commande $commande, string $successUrl, string $cancelUrl): StripeCheckoutSession
    {
        if ($commande->getStatut() !== 'pending_payment') {
            throw new \Exception(sprintf(
                "La commande #%d n'est pas prete pour le paiement (statut actuel: %s).",
                $commande->getId(),
                $commande->getStatut()
            ));
        }

        $key = trim($this->stripeSecretKey);
        if (!str_starts_with($key, 'sk_test_') && !str_starts_with($key, 'sk_live_')) {
            throw new \Exception('Cle Stripe invalide (sk_test_ ou sk_live_).');
        }
        Stripe::setApiKey($key);

        $lineItems = [];
        foreach ($commande->getLignesCommande() as $ligne) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $ligne->getProduit()->getNom(),
                    ],
                    'unit_amount' => $ligne->getPrix(),
                ],
                'quantity' => $ligne->getQuantite(),
            ];
        }

        $session = StripeCheckoutSession::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $commande->getId(),
            'metadata' => [
                'order_id' => (string) $commande->getId(),
            ],
        ]);

        $this->debugStripe('create', $commande->getId(), $session->id);

        return $session;
    }

    public function confirmPayment(Commande $commande, string $stripeSessionId): Payment
    {
        $key = trim($this->stripeSecretKey);
        if (!str_starts_with($key, 'sk_test_') && !str_starts_with($key, 'sk_live_')) {
            throw new \Exception('Cle Stripe invalide (sk_test_ ou sk_live_).');
        }
        Stripe::setApiKey($key);

        $session = StripeCheckoutSession::retrieve($stripeSessionId);
        $this->debugStripe('confirm', $commande->getId(), $stripeSessionId);
        if ($session->payment_status !== 'paid') {
            throw new \Exception('Paiement non confirme.');
        }

        $commande->setStatut('paid');
        $payment = $this->orderService->ensurePaymentRecordForPaidOrder($commande);
        if (!$payment instanceof Payment) {
            throw new \RuntimeException('Impossible de creer la trace de paiement.');
        }
        $this->entityManager->flush();

        return $payment;
    }

    private function debugStripe(string $action, int $orderId, string $sessionId): void
    {
        $key = trim($this->stripeSecretKey);
        $prefix = substr($key, 0, 7);
        $len = strlen($key);
        $line = sprintf(
            "[%s] action=%s order_id=%d session_id=%s key_prefix=%s key_len=%d\n",
            date('Y-m-d H:i:s'),
            $action,
            $orderId,
            $sessionId,
            $prefix,
            $len
        );
        @file_put_contents(__DIR__ . '/../../var/log/stripe_debug.log', $line, FILE_APPEND);
    }
}
