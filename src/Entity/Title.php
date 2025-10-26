<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasDefaultNormalizeCallback;
use App\Entity\Trait\HasLanguageTrait;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Entity\Trait\SoftDeletableTrait;
use App\Repository\TitleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use InvalidArgumentException;
use Symfony\Component\Serializer\Annotation\Groups;
use App\Entity\ArtistTitleContribution;

#[ORM\Entity(repositoryClass: TitleRepository::class)]
#[ORM\Index(name: 'ft_title_name', columns: ['name'], flags: ['fulltext'])]
class Title implements HasUploadedImagesInterface
{
    use HasUploadedImagesTrait;
    use HasLanguageTrait;
    /** @use HasDefaultNormalizeCallback<self> */
    use HasDefaultNormalizeCallback;
    use SoftDeletableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'titles')]
    private ?Publisher $publisher = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime  $releaseDate = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $isbn = null;

    /**
     * @var Collection<int, ArtistTitleContribution>
     */
    #[ORM\OneToMany(targetEntity: ArtistTitleContribution::class, mappedBy: 'title', orphanRemoval: true, cascade: ['remove'])]
    private Collection $artistsContributions;
    #[ORM\ManyToOne(inversedBy: 'titles')]
    private ?Series $series = null;

    #[ORM\ManyToOne(inversedBy: 'titles')]
    private ?PublisherCollection $collection = null;

    public function __construct()
    {
        $this->artistsContributions = new ArrayCollection();
    }

    public function getIsbn(): ?string
    {
        return $this->isbn;
    }

    public function setIsbn(?string $isbn): static
    {
        $this->isbn = $isbn;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
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
        $this->artistsContributions->removeElement($artistsContribution);
        return $this;
    }

    public function getSeries(): ?Series
    {
        return $this->series;
    }

    public function setSeries(?Series $series): static
    {
        $this->series = $series;

        return $this;
    }

    public function getCollection(): ?PublisherCollection
    {
        return $this->collection;
    }

    public function setCollection(?PublisherCollection $collection): static
    {
        $this->collection = $collection;

        return $this;
    }
}
