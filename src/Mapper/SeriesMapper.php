<?php

namespace App\Mapper;

use App\DTO\Series\SeriesWriteDTO;
use App\Entity\Publisher;
use App\Entity\Series;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @extends AbstractEntityMapper<Series, SeriesWriteDTO>
 */
class SeriesMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Series::class;
    }

    protected function instantiateEntity(): object
    {
        return new Series();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        $data = $this->normalizer->normalize($dto, 'array');
        $context = [AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImage', 'uploadedImages', 'publisher', 'titles']];
        if (!is_null($entity)) {
            $context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $entity;
        }
        /** @var Series $series */
        $series = $this->denormalizer->denormalize($data, Series::class, 'array', $context);
        $series = $this->afterDenormalization($dto, $series, $extra);
        /** @var Publisher $publisher */
        $publisher = $this->em->getReference(Publisher::class, $dto->publisher);
        $series->setPublisher($publisher);
        return $series;
    }
}
