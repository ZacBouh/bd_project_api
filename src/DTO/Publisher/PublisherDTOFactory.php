<?php

namespace App\DTO\Publisher;

use App\Entity\Publisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class PublisherDTOFactory
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private UploaderHelper $uploaderHelper,
        private LoggerInterface $logger
    ) {}

    public function createFromEntity(Publisher $publisher): PublisherReadDTO
    {
        $data = $this->normalizer->normalize($publisher, null, ['groups' => 'publisher:read']);

        if (!is_null($publisher->getCoverImage())) {
            $coverImage = $this->normalizer->normalize($publisher->getCoverImage(), null, ['groups' => ['titleReadDTO']]);
            $coverImage['url'] = $this->uploaderHelper->asset($publisher->getCoverImage(), 'file');
        }

        $uploadedImages = [];
        foreach ($publisher->getUploadedImages() as $image) {
            $uploadedImages[] = $this->normalizer->normalize($image, null, ['groups' => ['titleReadDTO']]);
        }

        $data['coverImage'] = $coverImage ?? [];
        $data['uploadedImages'] = $uploadedImages;

        $dto = $this->denormalizer->denormalize($data, PublisherReadDTO::class);
        return $dto;
    }
}
