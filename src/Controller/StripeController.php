<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeController extends AbstractController
{
    private $stripe;

    public function __construct()
    {
        $secret = $_ENV['STRIPE_SECRET_KEY'] ?? ($_ENV['STRIPE_SECRET'] ?? null);
        $this->stripe = new StripeClient($secret);
    }

    /**
     * @Route("/create-checkout-session", name="create_checkout")
     */
    public function createCheckoutSession(UrlGeneratorInterface $urlGenerator): RedirectResponse
    {
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Produit depuis site'],
                    'unit_amount' => 5000
                ],
                'quantity' => 1
            ]],
            'success_url' => $urlGenerator->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $urlGenerator->generate('checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return $this->redirect($session->url);
    }

    /**
     * Debug route: returns session id and url as JSON
     * @Route("/create-checkout-session-debug", name="create_checkout_debug")
     */
    public function createCheckoutSessionDebug(UrlGeneratorInterface $urlGenerator): JsonResponse
    {
        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'mode' => 'payment',
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Produit debug'],
                    'unit_amount' => 100
                ],
                'quantity' => 1
            ]],
            'success_url' => $urlGenerator->generate('checkout_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $urlGenerator->generate('checkout_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        return new JsonResponse(['id' => $session->id, 'url' => $session->url]);
    }

    /**
     * @Route("/checkout-success", name="checkout_success")
     */
    public function success()
    {
        return $this->render('stripe/success.html.twig');
    }

    /**
     * @Route("/checkout-cancel", name="checkout_cancel")
     */
    public function cancel()
    {
        return $this->render('stripe/cancel.html.twig');
    }
}
