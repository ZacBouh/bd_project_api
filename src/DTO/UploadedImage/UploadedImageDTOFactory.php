<?php

namespace App\DTO\UploadedImage;

use App\Entity\UploadedImage;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
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
            $entity->getCreatedAt()->format('Y-m-d'),
            $entity->getUpdatedAt()->format('Y-m-d'),
            $entity->getFileSize(),
            $entity->getOriginalFileName(),
            $entity->getFileMimeType(),
            $entity->getImageDimensions()
        );
    }

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): UploadedImageWriteDTO
    {
        $id = $this->getIdInput($inputBag);
        if (is_null($id)) {
            throw new InvalidArgumentException('Uploaded image id must be provided.');
        }

        $hasImageNameUpdate = $inputBag->has('imageName');
        $imageName = null;
        if ($hasImageNameUpdate) {
            $rawImageName = $inputBag->get('imageName');
            if (is_array($rawImageName)) {
                throw new InvalidArgumentException('imageName must be a string value.');
            }
            if (is_null($rawImageName)) {
                throw new InvalidArgumentException('imageName cannot be null.');
            }
            $trimmed = trim((string) $rawImageName);
            if ($trimmed === '') {
                throw new InvalidArgumentException('imageName cannot be empty.');
            }
            $imageName = $trimmed;
        }

        $imageFile = $this->getImageFile($files);

        return new UploadedImageWriteDTO($id, $imageName, $imageFile, $hasImageNameUpdate);
    }

    private function getIdInput(InputBag $inputBag): ?int
    {
        if (!$inputBag->has('id')) {
            return null;
        }
        $value = $inputBag->get('id');
        if (is_null($value)) {
            return null;
        }
        if (!is_string($value) && !is_int($value)) {
            throw new InvalidArgumentException('id must be a string or an integer.');
        }

        return (int) $value;
    }

    private function getImageFile(?FileBag $files): ?UploadedFile
    {
        if (is_null($files) || !$files->has('imageFile')) {
            return null;
        }

        $file = $files->get('imageFile');
        if (is_null($file)) {
            return null;
        }
        if (!$file instanceof UploadedFile) {
            throw new InvalidArgumentException('imageFile must be an instance of UploadedFile.');
        }

        return $file;
    }
}
