<?php

namespace App\Controller;

use App\DTO\PayoutTask\PayoutTaskDTOFactory;
use App\DTO\PayoutTask\PayoutTaskReadDTO;
use App\DTO\PayoutTask\PayoutTaskWriteDTO;
use App\Entity\User;
use App\Enum\PayoutTaskStatus;
use App\Exception\CopiesNotForSaleException;
use App\Service\OrderService;
use App\Service\PaymentService;
use App\Repository\UserRepository;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;


class PaymentController extends AbstractController
{
    public function __construct(
        private PaymentService $paymentService,
        private LoggerInterface $logger,
        private OrderService $orderService,
        private PayoutTaskDTOFactory $payoutTaskDTOFactory,
        private UserRepository $userRepository,
    ) {
    }

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

    #[Route('/api/payment/payout-tasks', name: 'payment_payout_tasks_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lister les tâches de paiement (administration)',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(
                name: 'userId',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                description: 'Identifiant du vendeur à filtrer. Si absent, toutes les tâches sont retournées.'
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Filtre optionnel sur le statut (valeur unique ou liste séparée par des virgules).'
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des tâches de paiement.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PayoutTaskReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès réservé aux administrateurs.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Paramètres invalides.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Utilisateur non trouvé.'),
        ]
    )]
    public function listPayoutTasks(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $seller = null;
        $userIdRaw = $request->query->get('userId');
        if ($userIdRaw !== null) {
            if (filter_var($userIdRaw, FILTER_VALIDATE_INT) === false) {
                return new JsonResponse(['error' => 'userId must be an integer'], Response::HTTP_BAD_REQUEST);
            }

            $seller = $this->userRepository->find((int) $userIdRaw);
            if (!$seller instanceof User) {
                return new JsonResponse(['error' => 'Seller not found'], Response::HTTP_NOT_FOUND);
            }
        }

        $rawStatuses = $request->query->all('status');
        if ($rawStatuses === []) {
            $singleStatus = $request->query->get('status');
            if (is_string($singleStatus) && $singleStatus !== '') {
                $rawStatuses = array_map('trim', explode(',', $singleStatus));
            } elseif (is_array($singleStatus)) {
                $rawStatuses = $singleStatus;
            }
        }

        $statuses = [];
        foreach ($rawStatuses as $statusValue) {
            if (!is_string($statusValue) || $statusValue === '') {
                return new JsonResponse(['error' => 'status must be a non-empty string'], Response::HTTP_BAD_REQUEST);
            }

            try {
                $statuses[] = PayoutTaskStatus::from($statusValue);
            } catch (\ValueError $exception) {
                return new JsonResponse(['error' => sprintf('Unknown status "%s"', $statusValue)], Response::HTTP_BAD_REQUEST);
            }
        }

        $statusFilter = $statuses !== [] ? $statuses : null;

        $tasks = $this->orderService->getPayoutTasks($seller, $statusFilter);

        $dtos = array_map(function ($task): PayoutTaskReadDTO {
            /** @var PayoutTaskReadDTO $dto */
            $dto = $this->payoutTaskDTOFactory->readDtoFromEntity($task);

            return $dto;
        }, $tasks);

        return $this->json($dtos, Response::HTTP_OK);
    }

    #[Route('/api/payment/payout-tasks/{id}', name: 'payment_payout_tasks_update', methods: ['PATCH'])]
    #[OA\Patch(
        summary: 'Mettre à jour une tâche de paiement',
        tags: ['Payments'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: new Model(type: PayoutTaskWriteDTO::class))
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Tâche mise à jour.', content: new OA\JsonContent(ref: new Model(type: PayoutTaskReadDTO::class))),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Tâche non trouvée.'),
        ]
    )]
    public function updatePayoutTask(int $id, Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $task = $this->orderService->findPayoutTask($id);
        if ($task === null) {
            return new JsonResponse(['error' => 'Payout task not found'], Response::HTTP_NOT_FOUND);
        }

        $payload = $request->getContent();
        $data = [];
        if ($payload !== '') {
            $decoded = json_decode($payload, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
            }
            if (is_array($decoded)) {
                $data = $decoded;
            } elseif ($decoded !== null) {
                return new JsonResponse(['error' => 'Invalid JSON body'], Response::HTTP_BAD_REQUEST);
            }
        }

        try {
            /** @var PayoutTaskWriteDTO $writeDto */
            $writeDto = $this->payoutTaskDTOFactory->writeDtoFromInputBag(new InputBag($data));
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $updatedTask = $this->orderService->updatePayoutTask($task, $writeDto);

        /** @var PayoutTaskReadDTO $dto */
        $dto = $this->payoutTaskDTOFactory->readDtoFromEntity($updatedTask);

        return $this->json($dto, Response::HTTP_OK);
    }

    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
