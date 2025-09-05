<?php

namespace App\Mapper;

use App\Entity\Artist;
use App\DTO\Artist\ArtistWriteDTO;
use App\Entity\Skill;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @extends AbstractEntityMapper<Artist, ArtistWriteDTO>
 */
class ArtistEntityMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Artist::class;
    }

    protected function instantiateEntity(): object
    {
        return new Artist();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        $data = $this->normalizer->normalize($dto, 'array');
        $this->logger->warning('Data after nomalization ' . json_encode($data));
        $artist = $this->denormalizer->denormalize($data, Artist::class, null, [AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImage', 'skills']]);
        $artist = $this->afterDenormalization($dto, $artist, $extra);
        if (!is_null($dto->skills)) {
            foreach ($dto->skills as $skill) {
                $ref = $this->em->getReference(Skill::class, $skill);
                if (!is_null($ref)) {
                    $artist->addSkill($ref);
                }
            }
        }
        return $artist;
    }
}
