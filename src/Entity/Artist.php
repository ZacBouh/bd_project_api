<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Repository\ArtistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ArtistRepository::class)]
class Artist implements HasUploadedImagesInterface
{
    use HasUploadedImagesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['artist:read'])]
    private ?int $id = null;

    #[Groups(['artist:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[Groups(['artist:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[Groups(['artist:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pseudo = null;

    #[Groups(['artist:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthDate = null;

    #[Groups(['artist:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $deathDate = null;

    #[Groups(['artist:read'])]
    #[ORM\ManyToMany(targetEntity: Skill::class)]
    #[ORM\JoinTable(name: "artist_skills")]
    #[ORM\JoinColumn(name: "artist_id", referencedColumnName: 'id', onDelete: "CASCADE")]
    #[ORM\InverseJoinColumn(name: "skill_name", referencedColumnName: "name")]
    private Collection $skills;

    /**
     * @var Collection<int, ArtistTitleContribution>
     */
    #[ORM\OneToMany(targetEntity: ArtistTitleContribution::class, mappedBy: 'artist', orphanRemoval: true)]
    private Collection $titlesContributions;


    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->titlesContributions = new ArrayCollection();
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
     * @return Collection<int, ArtistTitleContribution>
     */
    public function getTitlesContributions(): Collection
    {
        return $this->titlesContributions;
    }

    public function addTitlesContribution(ArtistTitleContribution $titlesContribution): static
    {
        if (!$this->titlesContributions->contains($titlesContribution)) {
            $this->titlesContributions->add($titlesContribution);
            $titlesContribution->setArtist($this);
        }

        return $this;
    }

    public function removeTitlesContribution(ArtistTitleContribution $titlesContribution): static
    {
        if ($this->titlesContributions->removeElement($titlesContribution)) {
            // set the owning side to null (unless already changed)
            if ($titlesContribution->getArtist() === $this) {
                $titlesContribution->setArtist(null);
            }
        }

        return $this;
    }

    public function getFullName(): string
    {
        $firstName = $this->firstName;
        $lastName = $this->lastName;
        return "$firstName $lastName";
    }
}
