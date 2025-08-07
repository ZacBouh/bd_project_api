<?php

namespace App\Service;

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

class CopyManagerService
{
    public function __construct(
        private LoggerInterface $logger,
        private CopyRepository $copyRepository,
        private Security $security,
        private UploadedImageService $imageService,
        private EntityManagerInterface $entityManager,
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
            ->setTileId($newCopyData->get('titleId'))
            ->setPrice($newCopyData->get('price'))
            ->setCurrency(PriceCurrency::from($newCopyData->get('currency')))
            ->setBoughtForPrice($newCopyData->get('boughtForPrice'))
            ->setBoughtForCurrency(PriceCurrency::from($newCopyData->get('boughtForCurrency')));

        $dataCopyConditionValue = $newCopyData->get('copyCondition');
        if (!CopyCondition::tryFrom($dataCopyConditionValue)) {
            throw new \InvalidArgumentException("Invalid copy condition : " . $dataCopyConditionValue);
        }
        $newCopy->setCopyCondition(CopyCondition::from($dataCopyConditionValue));
        if (!is_null($files) && !is_null($files->get('coverImageFile'))) {
            $this->imageService->saveUploadedCoverImage($newCopy, $files->get('coverImageFile'), "Copy Picture");
        }

        $this->entityManager->persist($newCopy);
        $this->entityManager->flush();

        return $newCopy;
    }
}
