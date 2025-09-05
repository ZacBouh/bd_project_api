<?php

namespace App\DTO\UploadedImage;

use App\Entity\UploadedImage;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadedImageDTOFactory
{
    public function __construct(
        private LoggerInterface $logger,
        private UploaderHelper $uploadHelper,
    ) {}

    public function readDtoFromEntity(UploadedImage $entity): UploadedImageReadDTO
    {
        if (is_null($entity->getId())) {
            $message = 'This entity is not persisted its id is null ' . $entity::class;
            $this->logger->error("UploadedImageDTOFactory : $message");
            throw new InvalidArgumentException($message);
        }
        $url = $this->uploadHelper->asset($entity, 'file');
        if (is_null($url)) {
            $message = 'Could not generate url for image with id ' . $entity->getId();
            $this->logger->error("UploadedImageDTOFactory : $message");
            throw new InvalidArgumentException($message);
        }

        return new UploadedImageReadDTO(
            $entity->getId(),
            $entity->getImageName(),
            $entity->getFileName(),
            $url,
            $entity->getCreatedAt()->format("Y-m-d"),
            $entity->getUpdatedAt()->format("Y-m-d"),
            $entity->getFileSize(),
            $entity->getOriginalFileName(),
            $entity->getFileMimeType(),
            $entity->getImageDimensions()
        );
    }
}
