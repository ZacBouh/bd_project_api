<?php

namespace App\Serializer;

use App\Entity\Artist;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ArtistSerializer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer
    ) {}

    public function getSupportedTypes(?string $format): array
    {
        return [
            Artist::class => true
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Artist;
    }

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $data = $this->normalizer->normalize($object, $format, $context);
        $data['skills'] = array_map(fn($skill) => $skill->getName(), $object->getSkills()->toArray());
        return $data;
    }
}
