<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\Copy\CopyDTOFactory;
use App\DTO\Copy\CopyWriteDTO;
use App\Entity\Copy;
use App\Entity\User;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Mapper\CopyMapper;
use App\Repository\CopyRepository;
use App\Security\Role;
use App\Service\CopyManagerService;
use App\Service\UploadedImageService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CopyManagerServiceTest extends TestCase
{
    /** @var LoggerInterface&MockObject */
    private LoggerInterface $logger;

    /** @var CopyRepository&MockObject */
    private CopyRepository $copyRepository;

    /** @var Security&MockObject */
    private Security $security;

    /** @var UploadedImageService&MockObject */
    private UploadedImageService $uploadedImageService;

    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $entityManager;

    /** @var CopyMapper&MockObject */
    private CopyMapper $copyMapper;

    /** @var ValidatorInterface&MockObject */
    private ValidatorInterface $validator;

    /** @var CopyDTOFactory&MockObject */
    private CopyDTOFactory $dtoFactory;

    private CopyManagerService $service;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->copyRepository = $this->createMock(CopyRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->uploadedImageService = $this->createMock(UploadedImageService::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->copyMapper = $this->createMock(CopyMapper::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->dtoFactory = $this->createMock(CopyDTOFactory::class);

        $this->service = new CopyManagerService(
            logger: $this->logger,
            copyRepository: $this->copyRepository,
            security: $this->security,
            imageService: $this->uploadedImageService,
            entityManager: $this->entityManager,
            copyMapper: $this->copyMapper,
            validator: $this->validator,
            dtoFactory: $this->dtoFactory,
        );
    }

    public function testRemoveCopyThrowsWhenUserIsNotOwnerAndNotAdmin(): void
    {
        /** @var User&MockObject $currentUser */
        $currentUser = $this->createMock(User::class);
        /** @var User&MockObject $owner */
        $owner = $this->createMock(User::class);

        /** @var Copy&MockObject $copy */
        $copy = $this->createMock(Copy::class);
        $copy->method('getOwner')->willReturn($owner);
        $copy->method('isDeleted')->willReturn(false);

        $this->security->method('getUser')->willReturn($currentUser);
        $this->security->method('isGranted')->with(Role::ADMIN->value)->willReturn(false);
        $this->copyRepository->method('findOneBy')->with(['id' => 42])->willReturn($copy);

        $this->entityManager->expects(self::never())->method('remove');
        $this->entityManager->expects(self::never())->method('persist');
        $this->entityManager->expects(self::never())->method('flush');

        $this->expectException(AccessDeniedException::class);

        $this->service->removeCopy(42);
    }

    public function testUpdateCopyKeepsOriginalOwnerForNonAdmin(): void
    {
        /** @var User&MockObject $currentUser */
        $currentUser = $this->createMock(User::class);
        $currentUser->method('getId')->willReturn(10);

        /** @var User&MockObject $owner */
        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn(10);

        /** @var Copy&MockObject $copy */
        $copy = $this->createMock(Copy::class);
        $copy->method('isDeleted')->willReturn(false);
        $copy->method('getOwner')->willReturn($owner);

        $this->security->method('getUser')->willReturn($currentUser);
        $this->security->method('isGranted')->with(Role::ADMIN->value)->willReturn(false);
        $this->copyRepository->method('findOneBy')->with(['id' => 123])->willReturn($copy);

        $dto = new CopyWriteDTO(
            owner: 99,
            title: 1,
            copyCondition: CopyCondition::POOR,
            id: 123,
            price: 1500,
            currency: PriceCurrency::EURO,
            boughtForPrice: null,
            boughtForCurrency: null,
            coverImageFile: null,
            uploadedImages: null,
            forSale: true,
        );

        $this->dtoFactory
            ->method('writeDtoFromInputBag')
            ->willReturn($dto);

        $this->copyMapper
            ->expects(self::once())
            ->method('fromWriteDTO')
            ->with(
                self::callback(function (CopyWriteDTO $dtoArgument): bool {
                    self::assertSame(10, $dtoArgument->owner);
                    return true;
                }),
                $copy,
                []
            )
            ->willReturn($copy);

        $this->entityManager->expects(self::once())->method('persist')->with($copy);
        $this->entityManager->expects(self::once())->method('flush');

        $result = $this->service->updateCopy(new InputBag(), new FileBag());

        self::assertSame($copy, $result);
        self::assertSame(10, $dto->owner);
    }
}
