<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Repository\TitleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: TitleRepository::class)]
class Title implements HasUploadedImagesInterface
{
    use HasUploadedImagesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['title:read'])]
    private ?int $id = null;

    #[Groups(['title:read'])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'titles')]
    private ?Publisher $publisher = null;

    #[Groups(['title:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime  $releaseDate = null;

    #[Groups(['title:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[Groups(['title:read'])]
    #  ISO 639-1 (2-letter codes like en, fr, es)
    #[ORM\Column(length: 2, nullable: true)]
    private ?string $language = null;

    /**
     * @var Collection<int, ArtistTitleContribution>
     */
    #[ORM\OneToMany(targetEntity: ArtistTitleContribution::class, mappedBy: 'title', orphanRemoval: true, cascade: ['remove'])]
    private Collection $artistsContributions;

    public function __construct()
    {
        $this->artistsContributions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function getReleaseDate(): ?\DateTime
    {
        return $this->releaseDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }


    public function getPublisher(): ?Publisher
    {
        return $this->publisher;
    }

    public function setPublisher(?Publisher $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }
    public function setReleaseDate(?\DateTime $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }
    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, ArtistTitleContribution>
     */
    public function getArtistsContributions(): Collection
    {
        return $this->artistsContributions;
    }

    public function addArtistsContribution(ArtistTitleContribution $artistsContribution): static
    {
        if (!$this->artistsContributions->contains($artistsContribution)) {
            $this->artistsContributions->add($artistsContribution);
            $artistsContribution->setTitle($this);
        }

        return $this;
    }

    public function removeArtistsContribution(ArtistTitleContribution $artistsContribution): static
    {
        if ($this->artistsContributions->removeElement($artistsContribution)) {
            // set the owning side to null (unless already changed)
            if ($artistsContribution->getTitle() === $this) {
                $artistsContribution->setTitle(null);
            }
        }
        return $this;
    }
}
