<?php

namespace App\DTO\UploadedImage;

use App\DTO\Builder\AbstractDTOBuilder;
use App\Entity\UploadedImage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @extends AbstractDTOBuilder<UploadedImage>
 */
class UploadedImageDTOBuilder extends AbstractDTOBuilder
{

    public function buildReadDTO(): UploadedImageReadDTO
    {
        return parent::denormalizeToDTO(UploadedImageReadDTO::class);
    }
}
