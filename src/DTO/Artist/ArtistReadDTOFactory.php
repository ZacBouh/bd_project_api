<?php

namespace App\DTO\Artist;

use App\Entity\Artist;
use App\Mapper\ImageMapper;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class ArtistReadDTOFactory
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
        private UploaderHelper $uploaderHelper,
        private LoggerInterface $logger,
        private ImageMapper $imageMapper,
    ) {}

    public function createFromEntity(Artist $artist): ArtistReadDTO
    {
        $data = $this->normalizer->normalize($artist, context: ['groups' => ['artist:read']]);
        $skillNames = [];
        foreach ($artist->getSkills() as $skill) {
            $skillNames[] = $skill->getName();
        }
        $data['skills'] = $skillNames;
        $date['coverImage'] = $this->imageMapper->mapWithUrl($artist->getCoverImage());
        $date['uploadedImages'] = $this->imageMapper->mapCollectionWithUrl($artist->getUploadedImages());
        return $this->denormalizer->denormalize($data, ArtistReadDTO::class);
    }

    public function createFromEntityWithContribution(Artist $artist): ArtistReadDTO
    {
        $data = $this->normalizer->normalize($artist, context: ['groups' => ['artist:read']]);
        $skillNames = [];
        foreach ($artist->getSkills() as $skill) {
            $skillNames[] = $skill->getName();
        }
        $date['coverImage'] = $this->imageMapper->mapWithUrl($artist->getCoverImage());
        $date['uploadedImages'] = $this->imageMapper->mapCollectionWithUrl($artist->getUploadedImages());
        $data['skills'] = $skillNames;
        $titlesContributions = [];
        foreach ($artist->getTitlesContributions() as $contribution) {
            $id = $contribution->getTitle()->getId();
            if (isset($titlesContributions[$id])) {
                $titlesContributions[$id]['skills'][] = $contribution->getSkill()->getName();
            } else {
                $titlesContributions[$id] = [
                    'artist' => $artist->getId(),
                    'title' => $contribution->getTitle()->getId(),
                    'skills' => [$contribution->getSkill()->getName()]
                ];
            }
        }
        $data['titlesContributions'] = array_values($titlesContributions);
        return $this
            ->denormalizer->denormalize($data, ArtistReadDTO::class);
    }
}
