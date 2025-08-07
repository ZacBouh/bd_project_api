<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
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

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['publisher:read'])]
    private ?int $id = null;

    #[Groups(['publisher:read'])]
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[Groups(['publisher:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $birthDate = null;

    #[Groups(['publisher:read'])]
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $deathDate = null;

    #[ORM\Column]
    #[Groups(['publisher:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['publisher:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

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

    public function __construct()
    {
        $this->titles = new ArrayCollection();
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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

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

    #[ORM\PrePersist]
    public function timestampOnCreate(): void
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function timestampOnUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
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
}
