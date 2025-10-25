<?php

namespace App\Controller;

use App\Exception\CopiesNotForSaleException;
use App\Service\PaymentService;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
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
    #[OA\Post(
        summary: 'Créer une session de paiement Stripe',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['copies'],
                properties: [
                    new OA\Property(
                        property: 'copies',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Identifiants des exemplaires à payer.'
                    ),
                    new OA\Property(
                        property: 'requestId',
                        type: 'string',
                        nullable: true,
                        description: 'Identifiant idempotent optionnel fourni par le client.'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'URL de paiement générée.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'url', type: 'string', format: 'uri')
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Erreurs de validation.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'validationErrors',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'field', type: 'string'),
                                    new OA\Property(property: 'message', type: 'string'),
                                ]
                            )
                        ),
                        new OA\Property(property: 'error', type: 'string')
                    ],
                    nullable: true
                )
            ),
            new OA\Response(
                response: Response::HTTP_CONFLICT,
                description: 'Certains exemplaires ne sont plus disponibles.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'error', type: 'string'),
                        new OA\Property(
                            property: 'unavailableCopyIds',
                            type: 'array',
                            items: new OA\Items(type: 'integer')
                        )
                    ]
                )
            )
        ]
    )]
    public function createStripeCheckoutSession(
        Request $request
    ): Response {
        try {
            $this->logger->debug(sprintf("got payment request for cart : %s ", $request->getContent()));
            /** @var array<string, mixed> $payload */
            $payload = json_decode($request->getContent(), true) ?? [];
            $requestId = $payload['requestId'] ?? null;
            $paymentUrl = $this->paymentService->getPaymentUrl($request, is_string($requestId) ? trim($requestId) : null);
            if (is_array($paymentUrl)) {
                return new JsonResponse(['validationErrors' => $paymentUrl], Response::HTTP_BAD_REQUEST);
            }
            return new JsonResponse(['url' => $paymentUrl], Response::HTTP_OK);
        } catch (CopiesNotForSaleException $exception) {
            return new JsonResponse(
                [
                    'error' => 'Some items are no longer available for sale.',
                    'unavailableCopyIds' => $exception->getCopyIds(),
                ],
                Response::HTTP_CONFLICT
            );
        } catch (InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/api/payment/stripe-webhook', name: 'payment_stripe_webhook', methods: 'POST')]
    #[OA\Post(
        summary: 'Webhook Stripe',
        description: 'Point d’entrée recevant les évènements Stripe Checkout.',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(required: true),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Évènement traité.')
        ]
    )]
    public function stripeEventWebhook(
        Request $request
    ): Response {
        $this->logger->debug(sprintf('Stripe Event : %s', $request->getContent()));

        $this->paymentService->handleStripeEvent($request);

        return new Response();
    }
}
