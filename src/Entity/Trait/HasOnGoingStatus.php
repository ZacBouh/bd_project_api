<?php

namespace App\Entity\Trait;

use App\Enum\OnGoingStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;


trait HasOnGoingStatus
{
    #[ORM\Column(length: 9, nullable: true, enumType: OnGoingStatus::class)]
    private ?OnGoingStatus $onGoingStatus = null;

    public function getOnGoingStatus(): ?OnGoingStatus
    {
        return $this->onGoingStatus;
    }

    public function setOnGoingStatus(null | string | OnGoingStatus $status): static
    {
        if ($status === '') {
            $status = null;
        }

        if (is_string($status)) {
            $status = OnGoingStatus::from($status);
        }

        $this->onGoingStatus = $status;

        return $this;
    }
}
