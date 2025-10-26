<?php

namespace App\Entity;

use App\Contract\Entity\HasUploadedImagesInterface;
use App\Entity\Trait\HasUploadedImagesTrait;
use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use App\Repository\CopyRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;

#[ORM\Entity(repositoryClass: CopyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Copy implements HasUploadedImagesInterface
{
    use TimestampableTrait;
    use SoftDeletableTrait;
    use HasUploadedImagesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    // #[Groups(['copy:read'])]
    private ?int $id = null;

    // #[Groups(['copy:read'])]
    #[ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[JoinColumn(name: 'owner_id', referencedColumnName: 'id')]
    private ?User $owner = null;

    // #[Groups(['copy:read'])]
    #[ManyToOne(targetEntity: Title::class, fetch: 'LAZY')]
    #[JoinColumn(name: 'title_id', referencedColumnName: 'id')]
    private ?Title $title = null;

    // #[Groups(['copy:read'])]
    #[ORM\Column(enumType: CopyCondition::class)]
    private CopyCondition $copyCondition;

    // #[Groups(['copy:read'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $price = null;

    // #[Groups(['copy:read'])]
    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $currency = null;

    // #[Groups(['copy:read'])]
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $boughtForPrice = null;

    // #[Groups(['copy:read'])]
    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $boughtForCurrency = null;

    #[ORM\Column(nullable: true)]
    private ?bool $forSale = null;

    public function getForSale(): bool
    {
        if (is_null($this->forSale)) {
            return false;
        }
        return $this->forSale;
    }

    public function setForSale(bool $forSale): static
    {
        $this->forSale = $forSale;
        return $this;
    }

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

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(?int $price): static
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

    public function getBoughtForPrice(): ?int
    {
        return $this->boughtForPrice;
    }

    public function setBoughtForPrice(?int $boughtForPrice): static
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
