<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Entity\PublisherCollection;
use App\Entity\Trait\HasDefaultNormalizeCallback;
use App\Repository\PublisherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: PublisherRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Publisher implements HasUploadedImagesInterface
{
    use HasUploadedImagesTrait;
    use TimestampableTrait;
    /** @use HasDefaultNormalizeCallback<self> */
    use HasDefaultNormalizeCallback;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['publisher:read'])]
    private ?int $id = null;

    #[Groups(['publisher:read'])]
    #[ORM\Column(length: 255)]
    private string $name;

    #[Groups(['publisher:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthDate = null;

    #[Groups(['publisher:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $deathDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['publisher:read'])]
    private ?string $description = null;

    # ISO 3166-1 alpha-2 codes (2-letter codes like US, FR, JP)
    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['publisher:read'])]
    private ?string $country = null;

    /**
     * @var Collection<int, Title>
     */
    #[ORM\OneToMany(targetEntity: Title::class, mappedBy: 'publisher')]
    private Collection $titles;

    /**
     * @var Collection<int, Series>
     */
    #[ORM\OneToMany(targetEntity: Series::class, mappedBy: 'publisher')]
    private Collection $series;

    /**
     * @var Collection<int, PublisherCollection>
     */
    #[ORM\OneToMany(targetEntity: PublisherCollection::class, mappedBy: 'publisher')]
    private Collection $collections;

    public function __construct()
    {
        $this->titles = new ArrayCollection();
        $this->series = new ArrayCollection();
        $this->collections = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCountry(): ?string
    {
        return $this->country;
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

    public function setCountry(string $country): static
    {
        $this->country = $country;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }


    /**
     * @return Collection<int, Title>
     */
    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function addtitles(Title $title): static
    {
        if (!$this->titles->contains($title)) {
            $this->titles->add($title);
            $title->setPublisher($this);
        }

        return $this;
    }

    public function removeTitle(Title $title): static
    {
        if ($this->titles->removeElement($title)) {
            // set the owning side to null (unless already changed)
            if ($title->getPublisher() === $this) {
                $title->setPublisher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Series $series): static
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
            $series->setPublisher($this);
        }

        return $this;
    }

    public function removeSeries(Series $series): static
    {
        $this->series->removeElement($series);

        return $this;
    }
    /**
     * @return Collection<int, PublisherCollection>
     */
    public function getCollections(): ?Collection
    {
        return $this->collections;
    }

    /**
     * @param Collection<int, PublisherCollection> $collections
     */
    public function setCollections(Collection $collections): static
    {
        $this->collections = $collections;

        return $this;
    }
}
