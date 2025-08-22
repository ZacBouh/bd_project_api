<?php

namespace App\Service;

use App\DTO\Copy\CopyDTOBuilder;
use App\DTO\Copy\CopyReadDTO;
use App\Entity\Copy;
use App\Entity\Title;
use App\Repository\CopyRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use APp\Entity\User;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Repository\TitleRepository;
use App\Repository\UserRepository;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CopyManagerService
{
    public function __construct(
        private LoggerInterface $logger,
        private CopyRepository $copyRepository,
        private Security $security,
        private UploadedImageService $imageService,
        private EntityManagerInterface $entityManager,
        private DenormalizerInterface $denormalizer,
        private NormalizerInterface $normalizer,
        private CopyDTOBuilder $dtoBuilder,
        private UserRepository $userRepository,
        private TitleRepository $titleRepository,
    ) {}

    public function createCopy(InputBag $newCopyData, ?FileBag $files = null): ?CopyReadDTO
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
        $newCopy = new Copy();

        $owner = $this->userRepository->findOneBy(['id' => $newCopyOwnerId]);
        /** @var Title $title */
        $title = $this->titleRepository->findOneBy(['id' =>  $newCopyData->get('titleId')]);
        $newCopy->setOwner($owner)
            ->setTitle($title)
            ->setPrice($newCopyData->get('price'))
            ->setCurrency(PriceCurrency::from($newCopyData->get('currency')))
            ->setBoughtForPrice($newCopyData->get('boughtForPrice'))
            ->setBoughtForCurrency(PriceCurrency::from($newCopyData->get('boughtForCurrency')));

        $dataCopyConditionValue = $newCopyData->get('copyCondition');
        if (!CopyCondition::tryFrom($dataCopyConditionValue)) {
            throw new \InvalidArgumentException("Invalid copy condition : " . $dataCopyConditionValue);
        }
        $newCopy->setCopyCondition(CopyCondition::from($dataCopyConditionValue));
        if (!is_null($files) && $files->count() > 0) {
            $this->imageService->saveUploadedCoverImage($newCopy, $files, "Copy Picture");
        } else {
            $newCopy->setCoverImage($title->getCoverImage());
        }

        $this->entityManager->persist($newCopy);
        $this->entityManager->flush();

        $dto = $this->dtoBuilder->fromEntity($newCopy)
            ->withUploadedImages()
            ->build();

        return $dto;
    }

    public function getCopies()
    {
        $copyDTOs = [];
        /** @var Copy[] */
        $copies = $this->copyRepository->findAllWithRelations();
        $this->logger->info("Retrieved " . count($copies) . " copies");
        foreach ($copies as $copy) {
            $dto = $this->dtoBuilder->fromEntity($copy, ['copy:read'])
                ->withUploadedImages()
                ->build();
            $copyDTOs[] = $dto;
            $this->logger->warning("built dto " . json_encode($dto));
        }
        return $copyDTOs;
    }

    public function removeCopy(CopyReadDTO $copyDTO)
    {
        /** @var User $user */
        $user = $this->security->getUser();
        /** @var Copy $copy */
        $copy = $this->copyRepository->findOneBy(['id' => $copyDTO->id]);
        if (is_null($copy)) {
            throw new ResourceNotFoundException('No copy was found with id ' . $copyDTO->id);
        }
        if ($copy->getOwner() !== $user && !$user->$this->isGranted(Role::ADMIN->value)) {
            throw new AccessDeniedException('Connected user does not have the right to remove a copy from another user library');
        }
        $this->entityManager->remove($copy);
        $this->entityManager->flush();
        return;
    }

    public function updateCopy(CopyReadDTO $copyDTO)
    {
        $this->logger->warning("Copy to update DTO: " . json_encode($copyDTO));
    }
}
