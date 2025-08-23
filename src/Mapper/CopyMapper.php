<?php

namespace App\Mapper;

use App\DTO\Copy\CopyReadDTO;
use App\Entity\Copy;
use App\Entity\UploadedImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Repository\TitleRepository;
use App\Repository\UploadedImageRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Entity\Title;
use App\Entity\User;

class CopyMapper
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private UserRepository $userRepository,
        private TitleRepository $titleRepository,
        private UploadedImageRepository $uploadedImageRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    public function fromDTO(CopyReadDTO $copyDTO, ?Copy $copy = null): Copy
    {
        if (is_null($copy)) {
            $copy = new Copy();
        }
        $data = $this->normalizer->normalize($copyDTO, 'array');
        $ignoredFields = [
            'id',
            'owner',
            'title',
            'createdAt',
            'updatedAt',
            'coverImage',
            'uploadedImages',
        ];
        foreach ($ignoredFields as $fieldName) {
            unset($data[$fieldName]);
        }

        $enumCallbacks = [
            'copyCondition' => fn($value) => $value instanceof CopyCondition ? $value : (is_null($value) ? null : CopyCondition::from($value)),
            'currency' => fn($value) => $value instanceof PriceCurrency ? $value : (is_null($value) ? null : PriceCurrency::from($value)),
            'boughtForCurrency' => fn($value) => $value instanceof PriceCurrency ? $value : (is_null($value) ? null : PriceCurrency::from($value)),
        ];

        $this->denormalizer->denormalize($data, Copy::class, 'array', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $copy,
            AbstractNormalizer::CALLBACKS => $enumCallbacks,
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
        ]);

        if (($copyDTO->owner['id'] ?? null) !== $copy->getOwner()?->getId()) {
            $user = $this->checkEntityExists(User::class, $copyDTO->owner['id']);
            $copy->setOwner($user);
        }

        if (($copyDTO->title['id'] ?? null) !== $copy->getTitle()?->getId()) {
            $title = $this->checkEntityExists(Title::class, $copyDTO->title['id']);
            $copy->setTitle($title);
        }

        if (($copyDTO->coverImage['id'] ?? null) !== $copy->getCoverImage()?->getId()) {
            $image = $this->checkEntityExists(UploadedImage::class, $copyDTO->coverImage['id']);
            $copy->setCoverImage($image);
        }

        return $copy;
    }

    public function checkEntityExists(string $entityClass, int|string $id): object
    {
        $repo = $this->entityManager->getRepository($entityClass);

        $result =  $repo->createQueryBuilder('e')
            ->select('1')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
        if (!$result) {
            throw new EntityNotFoundException('CopyMapper : no ' . $entityClass . ' found for id ' . $id);
        }

        return $this->entityManager->getReference($entityClass, $id);
    }
}
