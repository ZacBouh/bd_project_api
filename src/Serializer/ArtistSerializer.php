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
        /** @var Artist $object */
        $data = $this->normalizer->normalize($object, $format, ['groups' => 'artist:read']);
        $skillNames = [];
        foreach ($object->getSkills() as $skill) {
            $skillNames[] = $skill->getName();
        }
        $data['skills'] = $skillNames;
        $titlesContributions = [];
        foreach ($object->getTitlesContributions() as $contribution) {
            $id = $contribution->getTitle()->getId();
            if (isset($titlesContributions[$id])) {
                $titlesContributions[$id]['skills'][] = $contribution->getSkill()->getName();
            } else {
                $titlesContributions[$id] = [
                    'artist' => $contribution->getArtist()->getId(),
                    'title' => $contribution->getTitle()->getId(),
                    'skills' => [$contribution->getSkill()->getName()]
                ];
            }
        }
        $data['titlesContributions'] = array_values($titlesContributions);
        return $data;
    }
}
