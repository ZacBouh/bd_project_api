<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Copy;
use App\Entity\User;
use App\Enum\OrderItemStatus;
use App\Enum\PriceCurrency;
use App\Repository\OrderItemRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Copy $copy = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $seller = null;

    #[ORM\Column(type: 'integer')]
    private int $price = 0;

    #[ORM\Column(enumType: PriceCurrency::class)]
    private PriceCurrency $currency = PriceCurrency::EURO;

    #[ORM\Column(length: 32, enumType: OrderItemStatus::class)]
    private OrderItemStatus $status = OrderItemStatus::PENDING_HANDOVER;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $buyerConfirmedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }

    public function setOrder(?Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getCopy(): ?Copy
    {
        return $this->copy;
    }

    public function setCopy(?Copy $copy): self
    {
        $this->copy = $copy;

        return $this;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): PriceCurrency
    {
        return $this->currency;
    }

    public function setCurrency(PriceCurrency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getStatus(): OrderItemStatus
    {
        return $this->status;
    }

    public function setStatus(OrderItemStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getBuyerConfirmedAt(): ?DateTimeImmutable
    {
        return $this->buyerConfirmedAt;
    }

    public function setBuyerConfirmedAt(?DateTimeImmutable $buyerConfirmedAt): self
    {
        $this->buyerConfirmedAt = $buyerConfirmedAt;

        return $this;
    }

    public function markBuyerConfirmed(?DateTimeImmutable $confirmedAt = null): void
    {
        if ($this->status === OrderItemStatus::BUYER_CONFIRMED) {
            return;
        }

        $this->status = OrderItemStatus::BUYER_CONFIRMED;
        $this->buyerConfirmedAt = $confirmedAt ?? new DateTimeImmutable();
    }
}
