<?php

namespace App\Serializer;

use App\Entity\Publisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class PublisherSerializer implements NormalizerInterface
{
    public function __construct(
        #[Autowire(service: 'serializer.normalizer.object')]
        private NormalizerInterface $normalizer,
        private LoggerInterface $logger,
    ) {}

    public function getSupportedTypes(?string $format): array
    {
        return [
            Publisher::class => true
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Publisher;
    }

    public function normalize($object, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        /** @var Publisher $object */
        $data = $this->normalizer->normalize($object, $format, $context);
        $titles = [];
        foreach ($object->getTitles() as $title) {
            $titles[] = $title->getId();
        }
        $data['titles'] = $titles;
        return $data;
    }

    public function circularReferenceHandler($object, ?string $format, array $context): mixed
    {
        $this->logger->warning('circular cause : ' . json_encode($object));
        return $object->getName();
    }
}
