<?php

namespace App\DTO\UploadedImage;

use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(description: 'Payload pour mettre à jour une image téléversée.')]
class UploadedImageWriteDTO
{
    public function __construct(
        #[Assert\Positive]
        #[Assert\NotNull]
        #[OA\Property(description: 'Identifiant de l\'image téléversée', example: 42)]
        public ?int $id,

        #[Assert\NotBlank(allowNull: true)]
        #[OA\Property(description: 'Nom lisible de l\'image', nullable: true, example: 'Couverture édition spéciale')]
        public ?string $imageName,

        #[Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/*'],
            mimeTypesMessage: 'Veuillez téléverser un fichier image valide de moins de 10 Mo.'
        )]
        #[Ignore]
        #[OA\Property(property: 'imageFile', type: 'string', format: 'binary', nullable: true)]
        public ?UploadedFile $imageFile,

        #[Ignore]
        public bool $hasImageNameUpdate = false,
    ) {}
}
