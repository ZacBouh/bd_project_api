<?php

namespace App\Entity;

use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pseudo = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $deathDate = null;

    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: "artist_skills")]
    #[ORM\JoinColumn(name: "artist_id", referencedColumnName: 'id', onDelete: "CASCADE")]
    #[ORM\InverseJoinColumn(name: "skill_name", referencedColumnName: "name")]
    private Collection $skills;

    /**
     * @var Collection<int, Title>
     */
    #[ORM\ManyToMany(targetEntity: Title::class, mappedBy: 'artists')]
    private Collection $titles;


    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->titles = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): ?Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): Artist
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): Artist
    {
        $this->skills->removeElement($skill);
        return $this;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getBirthDate(): ?\DateTime
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTime $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getDeathDate(): ?\DateTime
    {
        return $this->deathDate;
    }

    public function setDeathDate(?\DateTime $deathDate): static
    {
        $this->deathDate = $deathDate;

        return $this;
    }

    /**
     * @return Collection<int, Title>
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function addTitle(Title $title): static
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->addArtist($this);
        }

        return $this;
    }

    public function removeTitle(Title $title): static
    {
        if ($this->titles->removeElement($title)) {
            $title->removeArtist($this);
        }

        return $this;
    }
}
