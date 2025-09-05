<?php

namespace App\DTO\PublisherCollection;

use App\Enum\Language;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;


class PublisherCollectionWriteDTO
{
    /**
     * @param array<mixed> $titles 
     */
    public function __construct(
        #[Assert\NotBlank(message: 'PublisherCollection name cannot be blank')]
        public string $name,

        #[Assert\Positive(message: 'Publisher id must be a positive integer')]
        public int $publisher,

        #[Assert\NotBlank(message: 'PublisherCollection language cannot be blank')]
        #[Assert\Choice(callback: 'languageCodes')]
        public string $language,

        #[Assert\Positive(message: 'PublisherCollection id must be a positive integer')]
        public ?int $id,

        #[Assert\All(constraints: [
            new Assert\Type('integer'),
            new Assert\Positive()
        ])]
        public ?array $titles,

        #[Assert\NotBlank(allowNull: true)]
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

    /**
     * @return string[]
     */
    public static function languageCodes()
    {
        return Language::getCodesList();
    }
}
