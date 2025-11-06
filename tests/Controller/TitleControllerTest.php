<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\TitleController;
use App\DTO\Title\TitleDTOFactory;
use App\Service\TitleManagerService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class TitleControllerTest extends TestCase
{
    public function testCreateTitleReturnsBadRequestWhenValidationFails(): void
    {
        $manager = $this->createMock(TitleManagerService::class);
        $dtoFactory = $this->createMock(TitleDTOFactory::class);
        $logger = $this->createMock(LoggerInterface::class);

        $controller = $this->createController($manager, $dtoFactory, $logger);

        $violations = new ConstraintViolationList([
            new ConstraintViolation(
                'The provided isbn is not in a valid format',
                null,
                [],
                null,
                'isbn',
                'invalid'
            ),
        ]);

        $exception = new ValidationFailedException(new \stdClass(), $violations);

        $manager
            ->method('createTitle')
            ->willThrowException($exception);

        $response = $controller->createTitle(new Request());

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());

        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('Validation failed', $payload['message'] ?? null);
        self::assertSame(
            ['isbn' => ['The provided isbn is not in a valid format']],
            $payload['errors'] ?? null
        );
}

    private function createController(
        TitleManagerService $manager,
        TitleDTOFactory $dtoFactory,
        LoggerInterface $logger
    ): TitleController {
        $controller = new TitleController($manager, $dtoFactory, $logger);
        $controller->setContainer(new class() implements ContainerInterface {
            public function get(string $id): mixed
            {
                throw new RuntimeException(sprintf('Service "%s" is not available in test container.', $id));
            }

            public function has(string $id): bool
            {
                return false;
            }
        });

        return $controller;
    }
}
