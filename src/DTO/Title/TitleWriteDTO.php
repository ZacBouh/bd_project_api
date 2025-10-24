<?php

namespace App\DTO\Title;

use App\Enum\Language;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(description: 'Payload utilisé pour créer ou mettre à jour un titre.')]
class TitleWriteDTO
{
    /**
     * @param array<array{artist: int, skills: string[]}> $artistsContributions
     * @param UploadedFile[] $uploadedImages
     */
    public function __construct(

        #[Assert\NotBlank]
        #[OA\Property(example: 'The Sandman')]
        public string $name,

        #[Assert\Positive]
        #[OA\Property(description: 'Identifiant du publisher', example: 5)]
        public int $publisher,

        #[Assert\NotBlank(message: 'Title is missing language information')]
        #[OA\Property(type: 'string', enum: ['ar','de','en','es','fr','hi','it','ja','ko','nl','pl','pt','ru','sv','tr','uk','zh'])]
        public ?Language $language,

        #[Assert\Positive]
        #[OA\Property(nullable: true, example: 12)]
        public ?int $id,

        #[Assert\NotBlank(allowNull: true)]
        #[OA\Property(nullable: true, example: 'Un résumé du titre')]
        public ?string $description,

        #[Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/*'],
            mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
        )]
        #[Ignore]
        #[OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true)]
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
        #[OA\Property(
            nullable: true,
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'artist', type: 'integer'),
                    new OA\Property(
                        property: 'skills',
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    )
                ]
            )
        )]
        public ?array $artistsContributions,

        #[Assert\AtLeastOneOf(constraints: [
            new Assert\Date(),
            new Assert\DateTime()
        ])]
        #[OA\Property(nullable: true, format: 'date')]
        public ?string $releaseDate,

        #[Assert\All(constraints: [
            new Assert\Image(
                maxSize: '10M',
                mimeTypes: ['image/*'],
                mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
            )
        ])]
        #[Ignore]
        #[OA\Property(type: 'array', nullable: true, items: new OA\Items(type: 'string', format: 'binary'))]
        public ?array $uploadedImages,

        #[Assert\Isbn(message: "The provided isbn is not in a valid format")]
        #[OA\Property(nullable: true, example: '9782070413119')]
        public ?string $isbn
    ) {}
}
