<?php

namespace App\DTO\Publisher;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class PublisherWriteDTO
{
    /**
     * @param UploadedFile[] $uploadedImages
     * @param mixed[] $titles
     */
    public function __construct(
        public string $name,

        #[Assert\Positive(message: 'PublisherCollection id must be a positive integer')]
        public ?int $id,


        #[Assert\NotBlank(allowNull: true)]
        public ?string $description,

        #[Assert\All(constraints: [
            new Assert\Type('integer'),
            new Assert\Positive()
        ])]
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
        #[Assert\All(constraints: [
            new Assert\Image(
                maxSize: '10M',
                mimeTypes: ['image/*'],
                mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
            )
        ])]
        public ?array $uploadedImages,
    ) {}
}
