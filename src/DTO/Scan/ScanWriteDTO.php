<?php

namespace App\DTO\Scan;

use App\Entity\UploadedImage;
use App\Enum\BookScanPart;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ScanWriteDTO
{
    public function __construct(
        public ?File $FRONT_COVER,
        public ?File $BACK_COVER,
        public ?File $SPINE,

        #[Assert\NotBlank(groups: ['create'])]
        public ?int $user,

        #[Assert\NotBlank(groups: ['update'])]
        #[Assert\Blank(groups: ['create'])]
        #[Groups(['update'])]
        public ?int $id
    ) {}

    #[Assert\Callback]
    public function validateAtLeastOneImage(ExecutionContextInterface $context): void
    {
        if (!$this->FRONT_COVER && !$this->BACK_COVER && !$this->SPINE) {
            $context->buildViolation('At least one image (front cover, back cover or spine) should be provided')
                ->addViolation();
        }
    }
}
