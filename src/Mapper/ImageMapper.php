<?php

namespace App\Mapper;

use App\Entity\UploadedImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ImageMapper
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private UploaderHelper $uploaderHelper,
    ) {}

    public function mapWithUrl(?UploadedImage $uploadedImage): ?array
    {
        if (!$uploadedImage) {
            return null;
        }

        $data = $this->normalizer->normalize($uploadedImage, null, ['groups' => ['uploadedImage:read']]);
        $data['url'] = $this->uploaderHelper->asset($uploadedImage, 'file');
        return $data;
    }

    /**
     * @param iterable<UploadedImage> $uploadedImages
     */
    public function mapCollectionWithUrl(iterable $uploadedImages): array
    {
        $mapped = [];
        foreach ($uploadedImages as $image) {
            $mapped[] = $this->mapWithUrl($image);
        }
        return $mapped;
    }
}
