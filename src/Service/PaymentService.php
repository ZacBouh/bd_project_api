<?php

namespace App\Service;

use App\Entity\CheckoutSessionEmail;
use App\Entity\Copy;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\PayoutTask;
use App\Enum\OrderPaymentStatus;
use App\Entity\StripeEvent;
use App\Entity\User;
use App\Exception\CopiesNotForSaleException;
use App\Enum\OrderItemStatus;
use App\Enum\PayoutTaskStatus;
use App\Enum\PriceCurrency;
use App\Service\MailerService;
use App\Repository\CheckoutSessionEmailRepository;
use App\Repository\CopyRepository;
use App\Repository\OrderRepository;
use App\Repository\StripeEventRepository;
use App\Repository\UserRepository;
use JsonException;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;
use UnexpectedValueException;

class PaymentService
{
    public function __construct(
        private StripeClient $stripe,
        private ValidatorInterface $validator,
        private CopyRepository $copyRepo,
        private OrderRepository $orderRepository,
        private StripeEventRepository $stripeEventRepository,
        private CheckoutSessionEmailRepository $checkoutSessionEmailRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private Security $security,
        private MailerService $mailService
    ) {}

    /**
     * @param Copy[] $copies
     */
    public function createStripeCheckoutSession(array $copies, ?string $requestId = null): string
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $items = [];
        $metadata = ['user' => $user->getId(), 'copies' => []];
        $copyIds = [];
        foreach ($copies as $copy) {
            $copyIds[] = (int) $copy->getId();
            $price = $copy->getPrice() ?? 0;
            $items[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $price,
                    'product_data' => [
                        'name' => $copy->getTitle()?->getName()
                    ]
                ],
                'quantity' => 1
            ];
            $metadata['copies'][] = [
                'id' => $copy->getId(),
                'name' => $copy->getTitle()?->getName() ?? 'No Name',
                'price' => $price,
                'currency' => $copy->getCurrency()?->value
            ];
        }

        sort($copyIds);
        $idempotencyKey = $requestId;
        if ($idempotencyKey === null) {
            $idempotencyKey = hash('sha256', sprintf('%s:%s', $user->getId(), implode('-', $copyIds)));
        }

        $this->logger->info('Creating Stripe checkout session', [
            'idempotency_key' => $idempotencyKey,
            'userId' => $user->getId(),
            'copyIds' => $copyIds,
        ]);

        try {
            $metadataJson = json_encode($metadata, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->logger->error('Unable to encode checkout session metadata', [
                'userId' => $user->getId(),
                'copyIds' => $copyIds,
                'error' => $exception->getMessage(),
            ]);
            throw new RuntimeException('Unable to encode checkout session metadata', 0, $exception);
        }

        $checkoutSession = $this->stripe->checkout->sessions->create([
            'line_items' => $items,
            'mode' => 'payment',
            'success_url' => 'http://localhost:8082/shopping-cart?success=true',
            'cancel_url' => 'http://localhost:8082/shopping-cart?canceled=true',
            'metadata' => ['items' => $metadataJson]
        ], [
            'idempotency_key' => $idempotencyKey,
        ]);

        if (is_null($checkoutSession->url)) {
            throw new RuntimeException('Checkout Session url is null');
        }

        return $checkoutSession->url;
    }

    /** @return string|array<array<string, string>> */
    public function getPaymentUrl(Request $request, ?string $requestId = null): string|array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($request->getContent(), true) ?? [];
        $copyIds = $data['copies'] ?? null;
        $errors = [];
        $violations = $this->validator->validate(
            $copyIds,
            new Assert\Sequentially([
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\Type('integer')
                ])
            ])
        );
        /** @var ConstraintViolationInterface $violation */
        foreach ($violations as $violation) {
            $errors[] = [
                'field' => $violation->getPropertyPath(),
                'message' => $violation->getMessage()
            ];
        }

        $requestId = $requestId ?? ($data['requestId'] ?? null);
        if ($requestId !== null && !is_string($requestId)) {
            $errors[] = [
                'field' => 'requestId',
                'message' => 'This value should be of type string.'
            ];
        }

        $requestId = is_string($requestId) ? trim($requestId) : null;
        if ($requestId === '') {
            $requestId = null;
        }
        if ($requestId !== null) {
            $requestIdViolations = $this->validator->validate($requestId, new Assert\Length(max: 255));
            /** @var ConstraintViolationInterface $violation */
            foreach ($requestIdViolations as $violation) {
                $errors[] = [
                    'field' => 'requestId',
                    'message' => $violation->getMessage()
                ];
            }
        }

        if ($errors !== []) {
            return $errors;
        }

        /** 
         * @var Copy[] $copies 
         * @var int[] $copyIds
         * */
        $copies = $this->copyRepo->findBy(['id' => $copyIds]);
        if (count($copies) !== count($copyIds)) {
            throw new InvalidArgumentException("Invalid Copy list, none or some where not found");
        }
        $notForSaleIds = $this->copyRepo->findNotForSaleIds($copyIds);
        if ($notForSaleIds !== []) {
            $this->logger->warning('Copies requested for checkout are not for sale', [
                'copyIds' => $notForSaleIds,
            ]);
            throw new CopiesNotForSaleException($notForSaleIds);
        }
        $url = $this->createStripeCheckoutSession($copies, $requestId);
        return $url;
    }

    public function handleStripeEvent(Request $request): void
    {
        $payload = $request->getContent();
        $signatureHeader = $request->headers->get('stripe-signature');
        $webHookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        if ($signatureHeader === null || $webHookSecret === null) {
            $this->logger->error('Stripe webhook signature or secret missing');
            throw new RuntimeException('Stripe webhook signature or secret missing');
        }

        try {
            $event = Webhook::constructEvent($payload, $signatureHeader, $webHookSecret);
        } catch (UnexpectedValueException | SignatureVerificationException $exception) {
            $this->logger->error('Stripe webhook signature verification failed', [
                'error' => $exception->getMessage(),
            ]);
            throw $exception;
        }

        $this->logger->info('Stripe webhook received', [
            'eventId' => $event->id,
            'type' => $event->type,
        ]);

        if ($this->stripeEventRepository->existsByEventId($event->id)) {
            $this->logger->info('Duplicate Stripe webhook ignored', [
                'eventId' => $event->id,
                'type' => $event->type,
            ]);
            return;
        }

        $decodedPayload = json_decode($payload, true) ?? [];
        $shouldSendEmail = false;
        $emailLog = null;
        $checkoutSession = null;

        $stripeEvent = (new StripeEvent())
            ->setEventId($event->id)
            ->setType($event->type)
            ->setPayload($decodedPayload);

        try {
            $this->entityManager->persist($stripeEvent);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $this->logger->info('Duplicate Stripe webhook ignored (race)', [
                'eventId' => $event->id,
                'type' => $event->type,
            ]);

            return;
        }

        if ($event->type === 'checkout.session.completed') {
            $checkoutSession = $event->data->object;
            if ($checkoutSession instanceof StripeCheckoutSession) {
                $shouldSendEmail = $this->processCheckoutSessionCompleted($checkoutSession, $emailLog);
                $this->entityManager->flush();
            }
        }

        if ($shouldSendEmail && $checkoutSession instanceof StripeCheckoutSession && $emailLog instanceof CheckoutSessionEmail) {
            $metadataJson = $checkoutSession->metadata['items'] ?? null;
            $metadata = is_string($metadataJson) ? json_decode($metadataJson, true) : null;
            if ($metadata === null && $metadataJson !== null) {
                $this->logger->warning('Unable to decode checkout session metadata for email', [
                    'sessionId' => $checkoutSession->id,
                ]);
            }
            $copyNames = [];
            $copiesMetadata = [];
            if (is_array($metadata) && isset($metadata['copies']) && is_array($metadata['copies'])) {
                $copiesMetadata = $metadata['copies'];
            }
            foreach ($copiesMetadata as $copyData) {
                if (isset($copyData['name'])) {
                    $copyNames[] = (string) $copyData['name'];
                } elseif (isset($copyData['id'])) {
                    $copyNames[] = sprintf('#%s', $copyData['id']);
                }
            }
            $copySummary = $copyNames === [] ? 'Unknown items' : implode(', ', $copyNames);
            $this->mailService->sendMail(
                to: 'zacharie.bouhaya@gmail.com',
                subject: 'Successful Purchase',
                content: sprintf(
                    'Your successfully purchased your comics books : %s',
                    $copySummary
                )
            );
            $emailLog->setSentAt(new DateTimeImmutable());
            $this->entityManager->flush();
        }
    }

    private function processCheckoutSessionCompleted(StripeCheckoutSession $session, ?CheckoutSessionEmail &$emailLog): bool
    {
        $metadataJson = $session->metadata['items'] ?? null;
        $metadata = is_string($metadataJson) ? json_decode($metadataJson, true) : null;

        if (!is_array($metadata)) {
            $this->logger->warning('Stripe checkout session metadata missing', [
                'sessionId' => $session->id,
            ]);
            return false;
        }

        $copyIds = [];
        foreach ($metadata['copies'] ?? [] as $copyData) {
            if (isset($copyData['id'])) {
                $copyIds[] = (int) $copyData['id'];
            }
        }
        $copyIds = array_values(array_filter($copyIds, static fn (int $id): bool => $id > 0));

        $user = null;
        if (isset($metadata['user'])) {
            $userId = (int) $metadata['user'];
            $user = $this->userRepository->find($userId);
            if ($user === null) {
                $this->logger->warning('User referenced in checkout metadata not found', [
                    'sessionId' => $session->id,
                    'userId' => $userId,
                ]);
            }
        }

        $copies = [];
        if ($copyIds !== []) {
            /** @var Copy[] $copies */
            $copies = $this->copyRepo->findBy(['id' => $copyIds]);
        }
        $copiesById = [];
        foreach ($copies as $copy) {
            $copyId = $copy->getId();
            if ($copyId !== null) {
                $copiesById[$copyId] = $copy;
            }
        }

        $order = $this->orderRepository->findOneByStripeCheckoutSessionId($session->id);
        if ($order === null) {
            $order = (new Order())
                ->setStripeCheckoutSessionId($session->id)
                ->setOrderRef($this->generateUniqueOrderRef())
                ->setUser($user);
            $this->entityManager->persist($order);
        } elseif ($order->getOrderRef() === '') {
            $order->setOrderRef($this->generateUniqueOrderRef());
        }

        if (is_array($metadata)) {
            $metadata['orderRef'] = $order->getOrderRef();
        }

        $order
            ->setAmountTotal($session->amount_total !== null ? (int) $session->amount_total : null)
            ->setCurrency($this->mapCurrency($session->currency))
            ->setMetadata($metadata)
            ->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER);

        $existingItemsByCopyId = [];
        foreach ($order->getItems() as $existingItem) {
            $copy = $existingItem->getCopy();
            $copyId = $copy?->getId();
            if ($copyId !== null) {
                $existingItemsByCopyId[$copyId] = true;
            }
        }

        foreach ($copyIds as $copyId) {
            $copy = $copiesById[$copyId] ?? null;
            if ($copy === null) {
                $this->logger->warning('Unable to attach copy to order item because it could not be loaded', [
                    'copyId' => $copyId,
                    'sessionId' => $session->id,
                ]);
                continue;
            }

            if (isset($existingItemsByCopyId[$copyId])) {
                continue;
            }

            $price = $copy->getPrice() ?? 0;
            $currency = $copy->getCurrency() ?? $this->mapCurrency($session->currency) ?? PriceCurrency::EURO;

            $orderItem = (new OrderItem())
                ->setCopy($copy)
                ->setSeller($copy->getOwner())
                ->setPrice($price)
                ->setCurrency($currency)
                ->setStatus(OrderItemStatus::PENDING_HANDOVER);

            $order->addItem($orderItem);
        }

        $updatedCopies = 0;
        if ($copyIds !== []) {
            $updatedCopies = $this->copyRepo->markAsSold($copyIds);
        }
        $this->logger->info('Marked copies as sold', [
            'sessionId' => $session->id,
            'updatedCount' => $updatedCopies,
            'copyIds' => $copyIds,
        ]);

        $this->initializePayoutTasks($order);

        if (!$this->checkoutSessionEmailRepository->existsForSession($session->id)) {
            $emailLog = (new CheckoutSessionEmail())
                ->setSessionId($session->id);
            $this->entityManager->persist($emailLog);

            return true;
        }

        return false;
    }

    private function mapCurrency(?string $currency): ?PriceCurrency
    {
        if ($currency === null) {
            return null;
        }

        return match (strtolower($currency)) {
            'eur', 'euro' => PriceCurrency::EURO,
            default => null,
        };
    }

    private function initializePayoutTasks(Order $order): void
    {
        $existingSellerIds = [];
        foreach ($order->getPayoutTasks() as $task) {
            $sellerId = $task->getSeller()?->getId();
            if ($sellerId !== null) {
                $existingSellerIds[$sellerId] = true;
            }
        }

        $amountBySeller = [];
        foreach ($order->getItems() as $item) {
            $seller = $item->getSeller();
            $sellerId = $seller?->getId();
            if ($seller === null || $sellerId === null) {
                continue;
            }

            if (!isset($amountBySeller[$sellerId])) {
                $amountBySeller[$sellerId] = [
                    'seller' => $seller,
                    'amount' => 0,
                    'currency' => $item->getCurrency(),
                ];
            }

            $amountBySeller[$sellerId]['amount'] += $item->getPrice();
        }

        foreach ($amountBySeller as $sellerId => $data) {
            if (isset($existingSellerIds[$sellerId])) {
                continue;
            }

            $currency = $data['currency'] ?? $order->getCurrency() ?? PriceCurrency::EURO;

            $task = (new PayoutTask())
                ->setSeller($data['seller'])
                ->setAmount((int) $data['amount'])
                ->setCurrency($currency)
                ->setStatus(PayoutTaskStatus::PENDING_PAYMENT_INFORMATION);

            $order->addPayoutTask($task);
        }
    }

    private function generateUniqueOrderRef(): string
    {
        $attempts = 0;

        do {
            if ($attempts++ >= 10) {
                throw new RuntimeException('Unable to generate a unique order reference');
            }

            try {
                $random = strtoupper(substr(bin2hex(random_bytes(5)), 0, 6));
            } catch (Throwable $exception) {
                throw new RuntimeException('Unable to generate order reference', 0, $exception);
            }

            $reference = sprintf('o_%s', $random);
        } while ($this->orderRepository->findOneBy(['orderRef' => $reference]) !== null);

        return $reference;
    }
}
