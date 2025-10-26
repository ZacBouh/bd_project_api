<?php

namespace App\Mapper;

use App\DTO\Artist\ArtistWriteDTO;
use App\Entity\Artist;
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
        $context = [
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ['coverImage', 'skills'],
        ];

        if (!is_null($entity)) {
            $context[AbstractObjectNormalizer::OBJECT_TO_POPULATE] = $entity;
        }

        /** @var Artist $artist */
        $artist = $this->denormalizer->denormalize($data, Artist::class, null, $context);
        $artist = $this->afterDenormalization($dto, $artist, $extra);

        if (!is_null($dto->skills)) {
            $existingSkills = $artist->getSkills();
            if (!is_null($existingSkills)) {
                foreach ($existingSkills as $existingSkill) {
                    $artist->removeSkill($existingSkill);
                }
            }

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
