<?php

namespace App\Mapper;

use App\Entity\Title;
use App\DTO\Title\TitleWriteDTO;
use App\Entity\Publisher;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @extends AbstractEntityMapper<Title, TitleWriteDTO>
 */
class TitleEntityMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Title::class;
    }

    protected function instantiateEntity(): object
    {
        return new Title();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        $data = $this->normalizer->normalize($dto, 'array');
        $context = [
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImage', 'uploadedImages', 'artistsContributions', 'publisher'],
        ];
        if (!is_null($entity)) {
            $context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $entity;
        }
        /** @var Title $title */
        $title = $this->denormalizer->denormalize($data, Title::class, null, $context);
        $title = parent::afterDenormalization($dto, $title, $extra);
        $publisher = $this->em->getReference(Publisher::class, $dto->publisher);
        $title->setPublisher($publisher);
        return $title;
    }

    /**
     * @return Title
     */
    protected function afterDenormalization(object $dto, object $entity, array $extra = []): object
    {
        $title = parent::afterDenormalization($dto, $entity, $extra);
        return $title;
    }
}
