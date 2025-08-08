<?php

namespace App\Service;

use App\DTO\Copy\CopyReadDTO;
use App\Entity\Copy;
use App\Repository\CopyRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use APp\Entity\User;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use Doctrine\ORM\EntityManagerInterface;
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
    ) {}

    public function createCopy(InputBag $newCopyData, ?FileBag $files = null): ?Copy
    {
        /**
         * @var User $user
         */
        $user = $this->security->getUser();
        $userId = $user->getId();
        $newCopyUserId =  (int) $newCopyData->get('ownerId');
        if ($userId !== $newCopyUserId) {
            $message = "User with id $userId cannot create a copy for user with id $newCopyUserId";
            throw new AccessDeniedException($message);
        }
        $newCopy = new Copy();
        $newCopy->setOwnerId($newCopyUserId)
            ->setTitleId($newCopyData->get('titleId'))
            ->setPrice($newCopyData->get('price'))
            ->setCurrency(PriceCurrency::from($newCopyData->get('currency')))
            ->setBoughtForPrice($newCopyData->get('boughtForPrice'))
            ->setBoughtForCurrency(PriceCurrency::from($newCopyData->get('boughtForCurrency')));

        $dataCopyConditionValue = $newCopyData->get('copyCondition');
        if (!CopyCondition::tryFrom($dataCopyConditionValue)) {
            throw new \InvalidArgumentException("Invalid copy condition : " . $dataCopyConditionValue);
        }
        $newCopy->setCopyCondition(CopyCondition::from($dataCopyConditionValue)); {
            $this->imageService->saveUploadedCoverImage($newCopy, $files, "Copy Picture");
        }

        $this->entityManager->persist($newCopy);
        $this->entityManager->flush();

        return $newCopy;
    }

    public function getCopies()
    {
        $copyDTOs = [];
        /** @var Copy[] */
        $copies = $this->copyRepository->findAllWithRelations();
        dump($copies);
        foreach ($copies as $copy) {
            // $this->logger->warning("COPY ID, OWNERID, TITLEID " . $copy->getId() . ' ' . $copy->getOwnerId() . ' ' . $copy->getTitleId());
            // $data = $this->normalizer->normalize($copy);
            // dump($data);
            // $copyDTOs[] = $this->denormalizer->denormalize($data, CopyReadDTO::class, context: [AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true]);
        }

        return $copyDTOs;
    }
}
