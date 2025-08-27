<?php

namespace App\Mapper;

use App\DTO\Series\SeriesWriteDTO;
use App\Entity\Publisher;
use App\Entity\Series;
use App\Entity\Title;
use App\Entity\UploadedImage;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SeriesMapper
{
    private array $denormalizerIgnoredFields;

    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private EntityManagerInterface $em,
    ) {
        $this->denormalizerIgnoredFields = [
            'publisherId',
            'titlesId',
            'coverImageFile',
            'id'
        ];
    }

    public function fromWriteDTO(SeriesWriteDTO $dto, ?UploadedImage $coverImage = null, ?Series $series = null): Series
    {
        if (!is_null($series)) {
            if (!is_null($dto->id) && $series->getId() !== $dto->id) {
                throw new InvalidArgumentException('Provided SeriesDTO id ' . $dto->id . ' does not match provided Series ' . $series->getId());
            }
        } elseif (!is_null($dto->id)) {
            $series = $this->em->find(Series::class, $dto->id);
            if (is_null($series)) {
                throw new InvalidArgumentException('No Series found for the provided SeriesDTO ' . $dto->id);
            }
        } else {
            $series = new Series();
        }
        /** @var Series $series */

        $data = $this->normalizer->normalize($dto, 'array');
        foreach ($this->denormalizerIgnoredFields as $field) {
            unset($data[$field]);
        }
        $this->denormalizer->denormalize($data, Series::class, 'array', [
            AbstractNormalizer::OBJECT_TO_POPULATE => $series,
            AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
            AbstractNormalizer::IGNORED_ATTRIBUTES => $this->denormalizerIgnoredFields
        ]);

        if (($dto->publisherId ?? null) !== $series->getPublisher()?->getId()) {
            $publisherProxy = $this->em->getReference(Publisher::class, $dto->publisherId);
            $series->setPublisher($publisherProxy);
        }

        if (isset($dto->titlesId) && count($dto->titlesId) > 0) {
            foreach ($dto->titlesId as $titleId) {
                $titleProxy = $this->em->getReference(Title::class, $titleId);
                /** @var Series $series */
                $series->addTitle($titleProxy);
            }
        }

        if (!is_null($coverImage)) {
            $series->setCoverImage($coverImage);
        }

        return $series;
    }
}
