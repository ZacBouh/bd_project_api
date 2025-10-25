<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Trait\TimestampableTrait;
use App\Entity\User;
use App\Enum\OrderPaymentStatus;
use App\Enum\PriceCurrency;
use App\Repository\OrderRepository;
use App\Entity\OrderItem;
use App\Entity\PayoutTask;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: 'orders')]
#[ORM\HasLifecycleCallbacks]
class Order
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 32, unique: true)]
    private string $orderRef = '';

    #[ORM\Column(length: 255, unique: true)]
    private string $stripeCheckoutSessionId = '';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $amountTotal = null;

    #[ORM\Column(nullable: true, enumType: PriceCurrency::class)]
    private ?PriceCurrency $currency = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(length: 16, enumType: OrderPaymentStatus::class)]
    private OrderPaymentStatus $status;

    /** @var Collection<int, OrderItem> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    /** @var Collection<int, PayoutTask> */
    #[ORM\OneToMany(mappedBy: 'order', targetEntity: PayoutTask::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $payoutTasks;

    public function __construct()
    {
        $this->orderRef = '';
        $this->stripeCheckoutSessionId = '';
        $this->status = OrderPaymentStatus::PENDING;
        $this->items = new ArrayCollection();
        $this->payoutTasks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getOrderRef(): string
    {
        return $this->orderRef;
    }

    public function setOrderRef(string $orderRef): self
    {
        $this->orderRef = $orderRef;

        return $this;
    }

    public function getStripeCheckoutSessionId(): string
    {
        return $this->stripeCheckoutSessionId;
    }

    public function setStripeCheckoutSessionId(string $stripeCheckoutSessionId): self
    {
        $this->stripeCheckoutSessionId = $stripeCheckoutSessionId;

        return $this;
    }

    public function getAmountTotal(): ?int
    {
        return $this->amountTotal;
    }

    public function setAmountTotal(?int $amountTotal): self
    {
        $this->amountTotal = $amountTotal;

        return $this;
    }

    public function getCurrency(): ?PriceCurrency
    {
        return $this->currency;
    }

    public function setCurrency(?PriceCurrency $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function getStatus(): OrderPaymentStatus
    {
        return $this->status;
    }

    public function setStatus(OrderPaymentStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PayoutTask>
     */
    public function getPayoutTasks(): Collection
    {
        return $this->payoutTasks;
    }

    public function addPayoutTask(PayoutTask $payoutTask): self
    {
        if (!$this->payoutTasks->contains($payoutTask)) {
            $this->payoutTasks->add($payoutTask);
            $payoutTask->setOrder($this);
        }

        return $this;
    }

    public function removePayoutTask(PayoutTask $payoutTask): self
    {
        if ($this->payoutTasks->removeElement($payoutTask)) {
            if ($payoutTask->getOrder() === $this) {
                $payoutTask->setOrder(null);
            }
        }

        return $this;
    }
}
