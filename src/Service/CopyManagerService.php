<?php

namespace App\Service;

use App\DTO\Copy\CopyDTOBuilder;
use App\DTO\Copy\CopyReadDTO;
use App\DTO\Copy\CopyWriteDTO;
use App\Entity\Copy;
use App\Entity\Title;
use App\Repository\CopyRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use App\Entity\User;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Mapper\CopyMapper;
use App\Repository\TitleRepository;
use App\Repository\UserRepository;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CopyManagerService
{
    public function __construct(
        private LoggerInterface $logger,
        private CopyRepository $copyRepository,
        private Security $security,
        private UploadedImageService $imageService,
        private EntityManagerInterface $entityManager,
        private CopyDTOBuilder $dtoBuilder,
        private CopyMapper $copyMapper,
        private ValidatorInterface $validator,
    ) {}

    /**
     * @param InputBag<string> $newCopyData
     */
    public function createCopy(InputBag $newCopyData, FileBag $files): Copy
    {
        /**
         * @var User $user
         */
        $user = $this->security->getUser();
        $userId = $user->getId();
        $newCopyOwnerId =  (int) $newCopyData->get('ownerId');
        if ($userId !== $newCopyOwnerId) {
            $message = "User with id $userId cannot create a copy for user with id $newCopyOwnerId";
            throw new AccessDeniedException($message);
        }
        $dto = $this->dtoBuilder->writeDTOFromInputBags($newCopyData, $files)->buildWriteDTO();
        $violation = $this->validator->validate($dto);
        if (count($violation) > 0) {
            throw new ValidationFailedException($dto, $violation);
        }
        $coverImage = null;
        if (!is_null($dto->coverImageFile)) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Copy Cover');
        }
        $entity = $this->copyMapper->fromWriteDTO($dto, extra: ['coverImage' => $coverImage]);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    /**
     * @return array<CopyReadDTO>
     */
    public function getCopies(): array
    {
        $copyDTOs = [];
        /** @var Copy[] */
        $copies = $this->copyRepository->findAllWithRelations();
        $this->logger->info("Retrieved " . count($copies) . " copies");
        foreach ($copies as $copy) {
            $dto = $this->dtoBuilder->readDTOFromEntity($copy)->buildReadDTO();
            $copyDTOs[] = $dto;
            // $this->logger->warning("built dto " . json_encode($dto));
        }
        return $copyDTOs;
    }

    public function removeCopy(CopyWriteDTO|string|int $copyDTO): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $copyId = $copyDTO instanceof CopyWriteDTO ? $copyDTO->id : $copyDTO;
        /** @var Copy|null $copy */
        $copy = $this->copyRepository->findOneBy(['id' => $copyId]);
        if (is_null($copy)) {
            throw new ResourceNotFoundException('No copy was found with id ' . $copyId);
        }
        if ($copy->getOwner() !== $user && !$this->security->isGranted(Role::ADMIN->value)) {
            throw new AccessDeniedException('Connected user does not have the right to remove a copy from another user library');
        }
        $this->entityManager->remove($copy);
        $this->entityManager->flush();
        return;
    }

    public function updateCopy(CopyWriteDTO $copyDTO): Copy
    {
        $this->logger->warning("Copy to update DTO: " . json_encode($copyDTO));

        /** @var Copy|null $copy */
        $copy = $this->copyRepository->findOneBy(['id' => $copyDTO->id]);
        if (is_null($copy)) {
            throw new ResourceNotFoundException("Update Copy : no copy found for id " . $copyDTO->id);
        }

        $this->copyMapper->fromWriteDTO($copyDTO, $copy);
        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return $copy;
    }
}
