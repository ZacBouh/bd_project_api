<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Copy;
use App\Entity\Title;
use App\Entity\User;
use App\Exception\CopiesNotForSaleException;
use App\Enum\PriceCurrency;
use App\Repository\CheckoutSessionEmailRepository;
use App\Repository\CopyRepository;
use App\Repository\OrderRepository;
use App\Repository\StripeEventRepository;
use App\Repository\UserRepository;
use App\Service\MailerService;
use App\Service\PaymentService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PaymentServiceTest extends TestCase
{
    private FakeStripeCheckoutSessions $stripeSessions;

    /** @var ValidatorInterface&MockObject */
    private ValidatorInterface $validator;

    /** @var CopyRepository&MockObject */
    private CopyRepository $copyRepository;

    /** @var OrderRepository&MockObject */
    private OrderRepository $orderRepository;

    /** @var StripeEventRepository&MockObject */
    private StripeEventRepository $stripeEventRepository;

    /** @var CheckoutSessionEmailRepository&MockObject */
    private CheckoutSessionEmailRepository $checkoutSessionEmailRepository;

    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    /** @var Security&MockObject */
    private Security $security;

    /** @var MailerService&MockObject */
    private MailerService $mailerService;

    private PaymentService $service;

    protected function setUp(): void
    {
        $this->stripeSessions = new FakeStripeCheckoutSessions();

        /** @var StripeClient&MockObject $stripeClient */
        $stripeClient = $this->getMockBuilder(StripeClient::class)
            ->disableOriginalConstructor()
            ->getMock();
        $stripeClient->checkout = new FakeStripeCheckout($this->stripeSessions);

        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->copyRepository = $this->createMock(CopyRepository::class);
        $this->orderRepository = $this->createMock(OrderRepository::class);
        $this->stripeEventRepository = $this->createMock(StripeEventRepository::class);
        $this->checkoutSessionEmailRepository = $this->createMock(CheckoutSessionEmailRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->mailerService = $this->createMock(MailerService::class);

        $this->service = new PaymentService(
            stripe: $stripeClient,
            validator: $this->validator,
            copyRepo: $this->copyRepository,
            orderRepository: $this->orderRepository,
            stripeEventRepository: $this->stripeEventRepository,
            checkoutSessionEmailRepository: $this->checkoutSessionEmailRepository,
            userRepository: $this->userRepository,
            entityManager: $this->entityManager,
            logger: $this->logger,
            security: $this->security,
            mailService: $this->mailerService,
        );
    }

    public function testCreateStripeCheckoutSessionUsesProvidedRequestId(): void
    {
        $this->mockAuthenticatedUser(42);

        $copies = [$this->createCopyStub(10)];

        $this->service->createStripeCheckoutSession($copies, 'custom-request-id');

        self::assertSame('custom-request-id', $this->stripeSessions->lastOptions['idempotency_key'] ?? null);
    }

    public function testCreateStripeCheckoutSessionGeneratesDeterministicRequestIdWhenMissing(): void
    {
        $this->mockAuthenticatedUser(99);

        $copies = [
            $this->createCopyStub(7),
            $this->createCopyStub(3),
        ];

        $this->service->createStripeCheckoutSession($copies, null);

        $expectedKey = hash('sha256', sprintf('%s:%s', 99, '3-7'));

        self::assertSame($expectedKey, $this->stripeSessions->lastOptions['idempotency_key'] ?? null);
    }

    public function testCreateStripeCheckoutSessionBuildsLineItemsFromCopies(): void
    {
        $this->mockAuthenticatedUser(7);

        $firstCopy = $this->createCopyStub(5, price: 1299, title: 'Amazing Spider-Man', currency: PriceCurrency::EURO);
        $secondCopy = $this->createCopyStub(8, price: 2500, title: 'Batman Year One', currency: null);

        $this->service->createStripeCheckoutSession([$firstCopy, $secondCopy], null);

        $params = $this->stripeSessions->lastParams;

        self::assertSame('payment', $params['mode']);
        self::assertCount(2, $params['line_items']);
        self::assertSame(1299, $params['line_items'][0]['price_data']['unit_amount']);
        self::assertSame('Amazing Spider-Man', $params['line_items'][0]['price_data']['product_data']['name']);
        self::assertSame(2500, $params['line_items'][1]['price_data']['unit_amount']);

        $metadata = json_decode($params['metadata']['items'], true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(7, $metadata['user']);
        self::assertSame([
            ['id' => 5, 'name' => 'Amazing Spider-Man', 'price' => 1299, 'currency' => 'euro'],
            ['id' => 8, 'name' => 'Batman Year One', 'price' => 2500, 'currency' => null],
        ], $metadata['copies']);
    }

    public function testGetPaymentUrlThrowsWhenCopiesNotForSale(): void
    {
        $this->mockAuthenticatedUser(12);

        $request = new Request([], [], [], [], [], [], json_encode([
            'copies' => [1, 2],
        ], JSON_THROW_ON_ERROR));

        $this->validator
            ->method('validate')
            ->willReturn(new ConstraintViolationList());

        $copyOne = $this->createCopyStub(1);
        $copyTwo = $this->createCopyStub(2);

        $this->copyRepository
            ->method('findBy')
            ->willReturn([$copyOne, $copyTwo]);

        $this->copyRepository
            ->method('findNotForSaleIds')
            ->willReturn([2]);

        $this->expectException(CopiesNotForSaleException::class);
        $this->expectExceptionMessage('Some items are no longer available for sale: 2');

        $this->service->getPaymentUrl($request);
    }

    public function testHandleStripeEventRejectsInvalidSignature(): void
    {
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_test';

        $request = new Request([], [], [], [], [], ['HTTP_STRIPE_SIGNATURE' => 'invalid'], '{"id":"evt_test","type":"payment_intent.created"}');

        $this->stripeEventRepository
            ->expects(self::never())
            ->method('existsByEventId');

        $this->expectException(SignatureVerificationException::class);

        $this->service->handleStripeEvent($request);
    }

    public function testHandleStripeEventProcessesEventOnlyOnceWhenDuplicateReceived(): void
    {
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_test';
        $payload = [
            'id' => 'evt_duplicate',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => ['id' => 'pi_123'],
            ],
        ];

        $firstRequest = $this->createSignedStripeRequest($payload);
        $duplicateRequest = $this->createSignedStripeRequest($payload);

        $this->stripeEventRepository
            ->expects(self::exactly(2))
            ->method('existsByEventId')
            ->with('evt_duplicate')
            ->willReturnOnConsecutiveCalls(false, true);

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(\App\Entity\StripeEvent::class));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->service->handleStripeEvent($firstRequest);
        $this->service->handleStripeEvent($duplicateRequest);
    }

    /**
     * @param Copy&MockObject $copy
     */
    private function createCopyStub(int $id, int $price = 10, ?string $title = null, ?PriceCurrency $currency = null): Copy
    {
        /** @var Copy&MockObject $copy */
        $copy = $this->createMock(Copy::class);
        $copy->method('getId')->willReturn($id);
        $copy->method('getPrice')->willReturn($price);
        if ($title !== null) {
            $titleEntity = $this->createMock(Title::class);
            $titleEntity->method('getName')->willReturn($title);
            $copy->method('getTitle')->willReturn($titleEntity);
        } else {
            $copy->method('getTitle')->willReturn(null);
        }
        $copy->method('getCurrency')->willReturn($currency);

        return $copy;
    }

    private function mockAuthenticatedUser(int $userId): void
    {
        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->security->method('getUser')->willReturn($user);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createSignedStripeRequest(array $payload): Request
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) time();
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'];
        $signature = hash_hmac('sha256', $timestamp . '.' . $body, $secret);
        $header = sprintf('t=%s,v1=%s', $timestamp, $signature);

        return new Request([], [], [], [], [], ['HTTP_STRIPE_SIGNATURE' => $header], $body);
    }
}

final class FakeStripeCheckout
{
    public function __construct(public FakeStripeCheckoutSessions $sessions)
    {
    }
}

final class FakeStripeCheckoutSessions
{
    /** @var array<string, mixed> */
    public array $lastOptions = [];

    /** @var array<string, mixed> */
    public array $lastParams = [];

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $options
     */
    public function create(array $params, array $options): object
    {
        $this->lastOptions = $options;
        $this->lastParams = $params;

        return (object) ['url' => 'https://example.test/checkout'];
    }
}
