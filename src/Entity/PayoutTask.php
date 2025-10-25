<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\PayoutTaskStatus;
use App\Enum\PriceCurrency;
use App\Repository\PayoutTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PayoutTaskRepository::class)]
class PayoutTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'payoutTasks')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $seller = null;

    #[ORM\Column(type: 'integer')]
    private int $amount = 0;

    #[ORM\Column(enumType: PriceCurrency::class)]
    private PriceCurrency $currency = PriceCurrency::EURO;

    #[ORM\Column(length: 32, enumType: PayoutTaskStatus::class)]
    private PayoutTaskStatus $status = PayoutTaskStatus::PENDING_PAYMENT_INFORMATION;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $paymentInformation = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paidAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

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

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

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

    public function getStatus(): PayoutTaskStatus
    {
        return $this->status;
    }

    public function setStatus(PayoutTaskStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPaymentInformation(): ?array
    {
        return $this->paymentInformation;
    }

    public function setPaymentInformation(?array $paymentInformation): self
    {
        $this->paymentInformation = $paymentInformation;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPaidAt(): ?DateTimeImmutable
    {
        return $this->paidAt;
    }

    public function setPaidAt(?DateTimeImmutable $paidAt): self
    {
        $this->paidAt = $paidAt;

        return $this;
    }
}
