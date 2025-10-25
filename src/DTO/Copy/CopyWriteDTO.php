<?php

namespace App\DTO\Copy;

use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

class CopyWriteDTO
{
    public function __construct(
        #[Assert\Positive]
        public int $owner,
        #[Assert\Positive]
        public int $title,
        public CopyCondition $copyCondition,
        #[Assert\Positive]
        public ?int $id,
        #[Assert\Positive]
        public ?int $price,
        public ?PriceCurrency $currency,
        #[Assert\Positive]
        public ?int $boughtForPrice,
        public ?PriceCurrency $boughtForCurrency,

        #[Assert\Image(
            maxSize: '10M',
            mimeTypes: ['image/*'],
            mimeTypesMessage: 'Please upload an image less than 10M in a valid image format'
        )]
        #[Ignore]
        public ?UploadedFile $coverImageFile,
        /**
         * @var array<UploadedFile>
         */
        public ?array $uploadedImages,

        public ?bool $forSale
    ) {}
}
