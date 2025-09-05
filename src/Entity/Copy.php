<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Repository\CopyRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: CopyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Copy implements HasUploadedImagesInterface
{
    use TimestampableTrait;
    use HasUploadedImagesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['copy:read'])]
    private ?int $id = null;

    #[Groups(['copy:read'])]
    #[ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    private ?User $owner = null;

    #[Groups(['copy:read'])]
    #[ManyToOne(targetEntity: Title::class, fetch: 'LAZY')]
    #[JoinColumn(name: 'title_id', referencedColumnName: 'id')]
    private ?Title $title = null;

    #[Groups(['copy:read'])]
    #[ORM\Column(enumType: CopyCondition::class)]
    private CopyCondition $copyCondition;

    #[Groups(['copy:read'])]
    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[Groups(['copy:read'])]
    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $currency = null;

    #[Groups(['copy:read'])]
    #[ORM\Column(nullable: true)]
    private ?float $boughtForPrice = null;

    #[Groups(['copy:read'])]
    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $boughtForCurrency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(User $owner): static
    {
        $this->owner = $owner;

        return $this;
    }

    public function getTitle(): ?Title
    {
        return $this->title;
    }

    public function setTitle(Title $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getCopyCondition(): CopyCondition
    {
        return $this->copyCondition;
    }

    public function setCopyCondition(CopyCondition $copyCondition): static
    {
        $this->copyCondition = $copyCondition;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): ?PriceCurrency
    {
        return $this->currency;
    }

    public function setCurrency(?PriceCurrency $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getBoughtForPrice(): ?float
    {
        return $this->boughtForPrice;
    }

    public function setBoughtForPrice(?float $boughtForPrice): static
    {
        $this->boughtForPrice = $boughtForPrice;

        return $this;
    }

    /**
     * @return PriceCurrency|null
     */
    public function getBoughtForCurrency(): ?PriceCurrency
    {
        return $this->boughtForCurrency;
    }

    public function setBoughtForCurrency(?PriceCurrency $boughtForCurrency): static
    {
        $this->boughtForCurrency = $boughtForCurrency;

        return $this;
    }
}
