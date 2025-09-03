<?php

namespace App\DTO\UploadedImage;

use App\DTO\Builder\AbstractDTOBuilder;
use App\Entity\UploadedImage;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @extends AbstractDTOBuilder<UploadedImage>
 */
class UploadedImageDTOBuilder extends AbstractDTOBuilder
{
    public function __construct(
        protected ContainerInterface $container,
        protected UploaderHelper $uploaderHelper,
    ) {
        parent::__construct($container);
    }

    public function readDTOFromEntity(object $entity): static
    {
        parent::readDTOFromEntity($entity);
        $this->data['url'] = $this->uploaderHelper->asset($entity, 'file');
        return $this;
    }

    public function buildReadDTO(): UploadedImageReadDTO
    {
        return parent::denormalizeToDTO(UploadedImageReadDTO::class);
    }
}
