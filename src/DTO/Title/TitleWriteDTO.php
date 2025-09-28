<?php

namespace App\DTO\Title;

use App\Enum\Language;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class TitleWriteDTO
{
    /**
     * @param array<array{artist: int, skills: string[]}> $artistsContributions
     * @param UploadedFile[] $uploadedImages
     */
    public function __construct(

        #[Assert\NotBlank]
        public string $name,

        #[Assert\Positive]
        public int $publisher,

        #[Assert\NotBlank(message: 'Title is missing language information')]
        public ?Language $language,

        #[Assert\Positive]
        public ?int $id,

        #[Assert\NotBlank(allowNull: true)]
        public ?string $description,

        #[Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/*'],
            mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
        )]
        #[Ignore]
        public ?UploadedFile $coverImageFile,

        #[Assert\All(
            constraints: [
                new Assert\Collection([
                    'fields' => [
                        'artist' => new Assert\Required([
                            new Assert\Type('integer'),
                            new Assert\Positive()
                        ]),
                        'skills' => new Assert\Required([
                            new Assert\Type('array'),
                            new Assert\Count(min: 1),
                            new Assert\All(
                                constraints: [
                                    new Assert\Type('string'),
                                    new Assert\NotBlank()
                                ]
                            )
                        ])
                    ],
                    'allowExtraFields' => false,
                    'allowMissingFields' => false,
                ])
            ]
        )]
        public ?array $artistsContributions,

        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Date(),
            new Assert\DateTime()
        ])]
        public ?string $releaseDate,

        #[Assert\All(constraints: [
            new Assert\Image(
                maxSize: '10M',
                mimeTypes: ['image/*'],
                mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
            )
        ])]
        #[Ignore]
        public ?array $uploadedImages,

        #[Assert\Isbn(message: "The provided isbn is not in a valid format")]
        public ?string $isbn
    ) {}
}
