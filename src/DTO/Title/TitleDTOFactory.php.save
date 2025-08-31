<?php

namespace App\DTO\Title;

use App\Entity\Title;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class TitleDTOFactory
{
    public function __construct(
        private NormalizerInterface $serializer,
        private LoggerInterface $logger,
        private UploaderHelper $uploaderHelper,
    ) {}

    public function createFromEntity(Title $title): TitleReadDTO
    {
        $artistsContributions = [];

        foreach ($title->getArtistsContributions() as $contribution) {
            $artistId = $contribution->getArtist()->getId();
            if (!isset($artistsContributions[$artistId])) {
                $artistsContributions[$artistId] = [
                    'artist' => [
                        'id' => $artistId,
                        'fullName' => $contribution->getArtist()->getFullName()
                    ],
                    'skills' => []
                ];
            }
            $artistsContributions[$artistId]['skills'][] = $contribution->getSkill()->getName();
        }

        $publisher = [
            'id' => $title->getPublisher()->getId(),
            'name' => $title->getPublisher()->getName()
        ];

        $coverImage = $this->serializer->normalize($title->getCoverImage(), null, ['groups' => ['titleReadDTO']]);
        if (!is_null($coverImage)) {
            $coverImage['url'] = $this->uploaderHelper->asset($title->getCoverImage(), 'file');
        } else {
            $coverImage = [];
        }

        $uploadedImages = [];
        foreach ($title->getUploadedImages() as $image) {
            $uploadedImages[] = $this->serializer->normalize($image, null, ['groups' => ['titleReadDTO']]);
        }

        return new TitleReadDTO(
            $title->getId(),
            $title->getName(),
            $title->getLanguage(),
            $title->getReleaseDate(),
            $title->getDescription(),
            $publisher,
            array_values($artistsContributions),
            $uploadedImages,
            $coverImage
        );
    }
}
