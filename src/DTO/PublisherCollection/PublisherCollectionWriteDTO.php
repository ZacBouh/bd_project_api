<?php

namespace App\DTO\PublisherCollection;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;
use Webmozart\Assert\Assert as AssertAssert;

class PublisherCollectionWriteDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'PublisherCollection name cannot be blank')]
        public string $name,

        #[Assert\Positive(message: 'Publisher id must be a positive integer')]
        public int $publisherId,

        #[Assert\All(constraints: [
            new Assert\Type('integer'),
            new Assert\Positive()
        ], message: 'Title ids must be positive integers')]
        public ?array $titleIds,

        public ?string $description,

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

    ) {}
}
