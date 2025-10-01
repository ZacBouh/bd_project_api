<?php

namespace App\Controller;

use App\Service\PaymentService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class PaymentController
{
    public function __construct(
        private PaymentService $paymentService,
        private LoggerInterface $logger
    ) {}

    /** @return RedirectResponse|JsonResponse */
    #[Route('/api/payment', name: 'payment_get_url', methods: 'POST')]
    public function createStripeCheckoutSession(
        Request $request
    ): Response {
        try {
            $this->logger->debug(sprintf("got payment request for cart : %s ", $request->getContent()));
            $paymentUrl = $this->paymentService->getPaymentUrl($request);
            if (is_array($paymentUrl)) {
                return new JsonResponse(['validationErrors' => $paymentUrl], Response::HTTP_BAD_REQUEST);
            }
            return new JsonResponse(['url' => $paymentUrl], Response::HTTP_OK);
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()]);
        }
    }

    #[Route('/api/payment/stripe-webhook', name: 'payment_stripe_webhook', methods: 'POST')]
    public function stripeEventWebhook(
        Request $request
    ): Response {
        $this->logger->debug(sprintf('Stripe Event : %s', $request->getContent()));

        $this->paymentService->handleStripeEvent($request);

        return new Response();
    }
}
