<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\AuthService;
use App\Service\MailerService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AuthServiceTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    /** @var UserPasswordHasherInterface&MockObject */
    private UserPasswordHasherInterface $passwordHasher;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var HttpClientInterface&MockObject */
    private HttpClientInterface $httpClient;

    /** @var UserRepository&MockObject */
    private UserRepository $userRepository;

    /** @var UrlGeneratorInterface&MockObject */
    private UrlGeneratorInterface $urlGenerator;

    /** @var MailerService&MockObject */
    private MailerService $mailerService;

    private AuthService $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->mailerService = $this->createMock(MailerService::class);

        $this->service = new AuthService(
            logger: $this->logger,
            passwordHasher: $this->passwordHasher,
            em: $this->entityManager,
            httpClient: $this->httpClient,
            userRepo: $this->userRepository,
            urlGenerator: $this->urlGenerator,
            mailService: $this->mailerService,
        );
    }

    public function testHandleEmailValidationUpdatesUser(): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setPseudo('user');
        $user->setEmailVerificationToken('valid-token');
        $user->setEmailVerificationExpiresAt(new DateTimeImmutable('+1 hour'));

        $this->userRepository
            ->method('findOneBy')
            ->with(['emailVerificationToken' => 'valid-token'])
            ->willReturn($user);

        $this->entityManager->expects(self::once())->method('flush');

        $result = $this->service->handleEmailValidation('valid-token');

        self::assertSame($user, $result);
        self::assertNull($user->getEmailVerificationToken());
        self::assertNull($user->getEmailVerificationExpiresAt());
        self::assertTrue($user->getEmailVerified());
    }

    public function testHandleEmailValidationThrowsWhenTokenUnknown(): void
    {
        $this->userRepository
            ->method('findOneBy')
            ->with(['emailVerificationToken' => 'missing-token'])
            ->willReturn(null);

        $this->entityManager->expects(self::never())->method('flush');

        $this->expectException(BadRequestException::class);

        $this->service->handleEmailValidation('missing-token');
    }

    public function testHandleEmailValidationThrowsWhenTokenExpired(): void
    {
        $user = (new User())
            ->setEmail('user@example.com')
            ->setPseudo('user');
        $user->setEmailVerificationToken('expired-token');
        $user->setEmailVerificationExpiresAt(new DateTimeImmutable('-1 minute'));

        $this->userRepository
            ->method('findOneBy')
            ->with(['emailVerificationToken' => 'expired-token'])
            ->willReturn($user);

        $this->entityManager->expects(self::never())->method('flush');

        $this->expectException(BadRequestException::class);

        $this->service->handleEmailValidation('expired-token');
    }
}
