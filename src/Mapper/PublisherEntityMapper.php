<?php

namespace App\Mapper;

use App\Entity\Publisher;
use App\DTO\Publisher\PublisherWriteDTO;
use App\Entity\Title;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @extends AbstractEntityMapper<Publisher, PublisherWriteDTO>
 */
class PublisherEntityMapper extends AbstractEntityMapper
{

    protected function getNormalizerCallbacks(): array
    {
        return [
            'titles' => function (array $titles) {
                $titlesRef = [];
                foreach ($titles as $title) {
                    $titlesRef[] = Title::normalizeCallback($title);
                }
                return $titlesRef;
            }
        ];
    }

    protected function getEntityClass(): string
    {
        return Publisher::class;
    }

    protected function instantiateEntity(): object
    {
        return new Publisher();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        $data = $this->normalizer->normalize($dto, 'array');
        $publisher = $this->denormalizer->denormalize($data, Publisher::class, null, [AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImage', 'titles', 'uploadedImages']]);
        $publisher = $this->afterDenormalization($dto, $publisher, $extra);
        return $publisher;
    }
}
