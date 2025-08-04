<?php

namespace App\ArgumentResolver;

use App\Entity\Title;
use App\Repository\ArtistRepository;
use App\Repository\PublisherRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\SerializerInterface;

class TitlePayloadResolver
{
    public function __construct(
        private ArtistRepository $artistRepository,
        private PublisherRepository $publisherRepository,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
    ) {}

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $argumentType = $argument->getType();
        if (!($argumentType === Title::class)) {
            return [];
        }
        $jsonContent = $request->getContent();
        /** @var Title $title */
        $title = $this->serializer->deserialize($jsonContent, Title::class, 'json', ['ignored_attributes' => ['artists']]);

        $data = json_decode($jsonContent, true);
        $artistsId = $data['artists'] ?? [];

        $artists = $this->artistRepository->findBy(['id' => $artistsId]);
        foreach ($artists as $artist) {
            $title->addArtist($artist);
        }

        yield $title;
    }
}
