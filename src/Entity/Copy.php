<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Repository\CopyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CopyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Copy implements HasUploadedImagesInterface
{
    use TimestampableTrait;
    use HasUploadedImagesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $ownerId = null;

    #[ORM\Column]
    private ?int $titleId = null;

    #[ORM\Column(enumType: CopyCondition::class)]
    private ?CopyCondition $copyCondition = null;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $currency = null;

    #[ORM\Column(nullable: true)]
    private ?float $boughtForPrice = null;

    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $boughtForCurrency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOwnerId(): ?int
    {
        return $this->ownerId;
    }

    public function setOwnerId(int $ownerId): static
    {
        $this->ownerId = $ownerId;

        return $this;
    }

    public function getTitleId(): ?int
    {
        return $this->titleId;
    }

    public function setTitleId(int $titleId): static
    {
        $this->titleId = $titleId;

        return $this;
    }

    public function getCopyCondition(): ?CopyCondition
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
