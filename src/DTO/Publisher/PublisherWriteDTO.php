<?php

namespace App\DTO\Publisher;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class PublisherWriteDTO
{
    /**
     * @param UploadedFile[] $uploadedImages
     * @param int[] $titles
     */
    public function __construct(
        #[Assert\Positive(message: 'PublisherCollection id must be a positive integer')]
        public ?int $id,
        public string $name,
        public ?string $description,
        public ?array $titles,

        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Date(),
            new Assert\DateTime()
        ])]
        public ?string $birthDate,

        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Date(),
            new Assert\DateTime()
        ])]
        public ?string $deathDate,

        #[Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/*'],
            mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
        )]
        #[Ignore]
        public ?UploadedFile $coverImageFile,
        public ?array $uploadedImages,
    ) {}
}
