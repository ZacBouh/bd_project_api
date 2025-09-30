<?php

namespace App\Service;

use App\DTO\Copy\CopyDTOFactory;
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
use InvalidArgumentException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
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
        private CopyMapper $copyMapper,
        private ValidatorInterface $validator,
        private CopyDTOFactory $dtoFactory,
    ) {}

    /**
     * @param InputBag<scalar> $newCopyData
     */
    public function createCopy(InputBag $newCopyData, FileBag $files): Copy
    {
        /**
         * @var User $user
         * @throws AccessDeniedException
         * @throws ValidationFailedException
         */
        $user = $this->security->getUser();
        $userId = $user->getId();
        $newCopyOwnerId =  (int) $newCopyData->get('ownerId');
        if ($userId !== $newCopyOwnerId) {
            $message = "User with id $userId cannot create a copy for user with id $newCopyOwnerId";
            throw new AccessDeniedException($message);
        }
        $dto = $this->dtoFactory->writeDTOFromInputBag($newCopyData, $files);
        $violation = $this->validator->validate($dto);
        if (count($violation) > 0) {
            throw new ValidationFailedException($dto, $violation);
        }
        $this->logger->debug('validated Copy Write DTO');
        $coverImage = null;
        if (!is_null($dto->coverImageFile)) {
            $this->logger->debug('CopyWriteDTO has a cover image file');
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, 'Copy Cover');
            $this->logger->debug(sprintf('Saved CopyWriteDTO cover image at id : %s', $coverImage->getId()));
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
            $dto = $this->dtoFactory->readDtoFromEntity($copy);
            $copyDTOs[] = $dto;
            // $this->logger->warning("built dto " . json_encode($dto));
        }
        return $copyDTOs;
    }

    /**
     * @throws ResourceNotFoundException
     * @throws AccessDeniedException
     */
    public function removeCopy(int $copyId): void
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $this->logger->debug("Looking for a copy to remove with id $copyId");
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

    /**
     * @param InputBag<scalar> $inputBag
     * @throws InvalidArgumentException
     * @throws ResourceNotFoundException
     */
    public function updateCopy(InputBag $inputBag, FileBag $files): Copy
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag, $files);
        $this->logger->warning(sprintf("Content of request: %s", json_encode($inputBag->all())));
        $this->logger->warning(sprintf('DTO For Sale status : %s ', json_encode($dto)));
        if (is_null($dto->id)) {
            throw new InvalidArgumentException('Update copy : id is null');
        }
        /** @var Copy|null $copy */
        $copy = $this->copyRepository->findOneBy(['id' => $dto->id]);
        if (is_null($copy)) {
            throw new ResourceNotFoundException("Update Copy : no copy found for id " . $dto->id);
        }

        $copy = $this->copyMapper->fromWriteDTO($dto, $copy);
        $this->logger->critical(sprintf("Copy For Sale status : %s", json_encode($copy->getForSale())));
        $this->entityManager->persist($copy);
        $this->entityManager->flush();

        return $copy;
    }

    /**
     * @return Copy[]
     */
    public function searchCopy(string $query, int $limit = 200, int $offset = 0, ?bool $forSale = null): array
    {
        if (trim($query, " \n\r\t\v\0") == "") {
            throw new InvalidArgumentException("Cannot search title with an empty string as query");
        }
        $queryWords = preg_split('/\s+/', trim($query));
        if ($queryWords === false) {
            throw new InvalidArgumentException('The query does not contain any valid word');
        }
        $queryWords = array_filter($queryWords); // to drop empty values
        $query = implode(' ', array_map(fn($word) => "$word*", $queryWords));
        $copies = $this->copyRepository->searchCopy($query, $limit, $offset, $forSale);
        return $copies;
    }
}
