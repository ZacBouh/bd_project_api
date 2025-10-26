<?php

namespace App\Mapper;

use App\DTO\UploadedImage\UploadedImageWriteDTO;
use App\Entity\UploadedImage;
use InvalidArgumentException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

class UploadedImageEntityMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return UploadedImage::class;
    }

    protected function instantiateEntity(): object
    {
        return new UploadedImage();
    }

    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        if (!$dto instanceof UploadedImageWriteDTO) {
            throw new InvalidArgumentException('UploadedImageEntityMapper expects an UploadedImageWriteDTO instance.');
        }

        if (is_null($dto->id)) {
            throw new InvalidArgumentException('Uploaded image id must be provided.');
        }

        if (!is_null($entity) && !$entity instanceof UploadedImage) {
            throw new InvalidArgumentException('UploadedImageEntityMapper expects an UploadedImage entity instance.');
        }

        if (is_null($entity)) {
            $entity = $this->em->find($this->getEntityClass(), $dto->id);
        }

        if (!$entity instanceof UploadedImage) {
            throw new ResourceNotFoundException('No uploaded image was found for id ' . $dto->id);
        }

        if ($dto->hasImageNameUpdate) {
            if (is_null($dto->imageName)) {
                throw new InvalidArgumentException('imageName cannot be empty.');
            }
            $entity->setImageName($dto->imageName);
        }

        if (!is_null($dto->imageFile)) {
            $entity->setFile($dto->imageFile);
        }

        return $entity;
    }
}
