<?php

namespace App\Entity;

use App\Repository\ArtistTitleContributionRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistTitleContributionRepository::class)]
class ArtistTitleContribution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'titlesContributions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Artist $artist = null;

    #[ORM\ManyToOne(inversedBy: 'artistsContributions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Title $title = null;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false, referencedColumnName: "name", name: 'skill_name')]
    private ?Skill $skill = null;

    public function __construct() {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getArtist(): ?Artist
    {
        return $this->artist;
    }

    public function setArtist(?Artist $artist): static
    {
        $this->artist = $artist;

        return $this;
    }

    public function getTitle(): ?Title
    {
        return $this->title;
    }

    public function setTitle(?Title $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setSkill(Skill $skill): static
    {
        $this->skill = $skill;

        return $this;
    }

    public function getSkill(): Skill
    {
        return $this->skill;
    }
}
