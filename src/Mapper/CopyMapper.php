<?php

namespace App\Mapper;

use App\DTO\Copy\CopyWriteDTO;
use App\Entity\Copy;
use App\Entity\UploadedImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Entity\Title;
use App\Entity\User;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @extends AbstractEntityMapper<Copy, CopyWriteDTO>
 */
class CopyMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Copy::class;
    }

    protected function instantiateEntity(): object
    {
        return new Copy();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        $data = $this->normalizer->normalize($dto, 'array');
        $this->logger->debug('Normalized CopyWriteDTO');
        /** @var Copy $copy */
        $copy = $this->denormalizer->denormalize($data, Copy::class, context: [
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => ["owner", "title", 'coverImage', 'uploadedImages']
        ]);
        $this->logger->debug("Denormalized CopyWrite data to new Copy");
        $copy->setOwner($this->em->getReference(User::class, $dto->owner)); //@phpstan-ignore-line
        $copy->setTitle($this->em->getReference(Title::class, $dto->title)); //@phpstan-ignore-line
        $copy = parent::afterDenormalization($dto, $copy, $extra);
        return $copy;
    }
}
