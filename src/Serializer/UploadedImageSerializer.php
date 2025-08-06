<?php

namespace App\Serializer;

use App\Entity\UploadedImage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UploadedImageSerializer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private UploaderHelper $uploaderHelper,
    ) {}

    public function getSupportedTypes(?string $format): array
    {
        return [
            UploadedImage::class => true
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof UploadedImage;
    }

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        /** @var UploadedImage $object */
        $data = $this->normalizer->normalize($object, $format);
        unset($data['file']);
        $data['url'] = $this->uploaderHelper->asset($object, 'file');
        return $data;
    }
}
