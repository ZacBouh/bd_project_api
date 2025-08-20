<?php

namespace App\Entity\Trait;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

trait TimestampableTrait
{
    #[ORM\Column(type: 'datetime_immutable')]
    #[Groups(['timestamp:read'])]
    public \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['timestamp:read'])]
    public \DateTime $updatedAt;

    #[ORM\PrePersist]
    public function initializeTimestamps(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }
}
