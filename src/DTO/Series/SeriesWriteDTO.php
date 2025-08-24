<?php

namespace App\DTO\Series;

use App\Enum\Language;
use App\Enum\OnGoingStatus;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class SeriesWriteDTO
{
    public function __construct(
        #[Assert\NotBlank(message: 'Series name cannot be blank')]
        public string $name,
        #[Assert\Positive(message: 'Publisher id must be a positive integer')]
        public int $publisherId,
        #[Assert\NotNull(message: 'Series must have a language')]
        public Language $language,
        public ?int $id,
        #[Assert\All(constraints: [
            new Assert\Type('integer'),
            new Assert\Positive()
        ])]
        public ?array $titlesId,
        public ?OnGoingStatus $onGoingStatus,
        #[Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/*'],
            mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
        )]
        #[Ignore]
        public ?UploadedFile $coverImageFile
    ) {}
}
