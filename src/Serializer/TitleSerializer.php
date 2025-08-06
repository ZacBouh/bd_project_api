<?php

namespace App\Serializer;

use App\Entity\Title;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TitleSerializer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer
    ) {}

    public function getSupportedTypes(?string $format): array
    {
        return [
            Title::class => true
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Title;
    }

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        /** @var Title $object */
        $data = $this->normalizer->normalize($object, $format, ['groups' => ['title:read']]);
        $data['publisher'] = $object->getPublisher()->getId();
        $artistsContributions = [];
        foreach ($object->getArtistsContributions() as $contribution) {
            $id = $contribution->getArtist()->getId();
            if (isset($artistsContributions[$id])) {
                $artistsContributions[$id]['skills'][] = $contribution->getSkill()->getName();
            } else {
                $artistsContributions[$id] = [
                    'artist' => $contribution->getArtist()->getId(),
                    'title' => $contribution->getTitle()->getId(),
                    'skills' => [$contribution->getSkill()->getName()]
                ];
            }
        }
        $data['artistsContributions'] = array_values($artistsContributions);
        return $data;
    }
}
