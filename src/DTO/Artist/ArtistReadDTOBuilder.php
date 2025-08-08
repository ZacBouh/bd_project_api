<?php

namespace App\DTO\Artist;

use App\DTO\Builder\DTOBuilder;
use App\Entity\Artist;

class ArtistReadDTOBuilder
{
    public function __construct(
        private DTOBuilder $builder,
    ) {}

    public function fromEntity(Artist $artist): static
    {
        $skillNames = [];
        foreach ($artist->getSkills() as $skill) {
            $skillNames[] = $skill->getName();
        }
        $this->builder
            ->fromEntity($artist, 'artist:read')
            ->addField('skills', $skillNames);
        return $this;
    }

    public function withContributions(): static
    {
        $artist = $this->builder->getEntity();
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
        $this->builder->addField('titlesContributions', array_values($titlesContributions));
        return $this;
    }

    public function withCoverImage(): static
    {
        $this->builder->addCoverImage();
        return $this;
    }

    public function withUploadedImages(): static
    {
        $this->builder->addUploadedImages();
        return $this;
    }

    public function build(?string $dtoClass = ArtistReadDTO::class)
    {
        return $this->builder->build($dtoClass);
    }
}
