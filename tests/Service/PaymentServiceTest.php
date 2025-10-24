<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Copy;
use App\Entity\User;
use App\Exception\CopiesNotForSaleException;
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

    /** @var Security&MockObject */
    private Security $security;

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
        $orderRepository = $this->createMock(OrderRepository::class);
        $stripeEventRepository = $this->createMock(StripeEventRepository::class);
        $checkoutSessionEmailRepository = $this->createMock(CheckoutSessionEmailRepository::class);
        $userRepository = $this->createMock(UserRepository::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $mailerService = $this->createMock(MailerService::class);

        $this->service = new PaymentService(
            stripe: $stripeClient,
            validator: $this->validator,
            copyRepo: $this->copyRepository,
            orderRepository: $orderRepository,
            stripeEventRepository: $stripeEventRepository,
            checkoutSessionEmailRepository: $checkoutSessionEmailRepository,
            userRepository: $userRepository,
            entityManager: $entityManager,
            logger: $logger,
            security: $this->security,
            mailService: $mailerService,
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

    /**
     * @param Copy&MockObject $copy
     */
    private function createCopyStub(int $id): Copy
    {
        /** @var Copy&MockObject $copy */
        $copy = $this->createMock(Copy::class);
        $copy->method('getId')->willReturn($id);
        $copy->method('getPrice')->willReturn(10.0);
        $copy->method('getTitle')->willReturn(null);
        $copy->method('getCurrency')->willReturn(null);

        return $copy;
    }

    private function mockAuthenticatedUser(int $userId): void
    {
        /** @var User&MockObject $user */
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->security->method('getUser')->willReturn($user);
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

    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $options
     */
    public function create(array $params, array $options): object
    {
        $this->lastOptions = $options;

        return (object) ['url' => 'https://example.test/checkout'];
    }
}
