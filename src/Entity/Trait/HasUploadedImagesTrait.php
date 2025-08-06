<?php

namespace App\Entity\Trait;

use App\Entity\UploadedImage;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

trait HasUploadedImagesTrait
{
    /**
     * @var Collection<int, UploadedImage>
     */
    #[Groups(['title:read'])]
    #[ORM\ManyToMany(targetEntity: UploadedImage::class)]
    private ?Collection $uploadedImages = null;

    #[Groups(['title:read'])]
    #[ORM\Column(nullable: true)]
    private ?int $coverImageId = null;

    /**
     * @return Collection<int, UploadedImage>
     */
    public function getUploadedImages(): Collection
    {
        return $this->uploadedImages;
    }

    public function addUploadedImage(UploadedImage $uploadedImage): static
    {
        if (!$this->uploadedImages->contains($uploadedImage)) {
            $this->uploadedImages->add($uploadedImage);
        }

        return $this;
    }

    public function removeUploadedImage(UploadedImage | int $image): static
    {
        if ($image instanceof UploadedImage) {
            $this->uploadedImages->removeElement($image);
            if ($this->coverImageId === $image->getId()) $this->coverImageId = null;
            return $this;
        }

        if (is_int($image)) {
            if ($this->coverImageId === $image) $this->coverImageId = null;
            $image = $this->getUploadedImagesById($image);
            if (is_null($image)) return $this;
            $this->uploadedImages->removeElement($image);
            return $this;
        }

        throw new InvalidArgumentException(static::class . " removeUploadedImage accepts only int or " . UploadedImage::class . "as argument" . get_debug_type($image) . " given");
    }

    public function getCoverImage(): ?UploadedImage
    {
        $coverImageId = $this->coverImageId;
        if (is_null($coverImageId)) {
            return null;
        }
        foreach ($this->uploadedImages as $image) {
            if ($image->getId() === $coverImageId) {
                return $image;
            }
        }
        return null;
    }

    public function getUploadedImageById(int $imageId): ?UploadedImage
    {
        foreach ($this->uploadedImages as $image) {
            if ($image->getId() === $imageId) {
                return $image;
            }
        }
        return null;
    }

    public function setCoverImage(?UploadedImage $coverImage): static
    {
        if (is_null($coverImage)) {
            $this->coverImageId = null;
            return $this;
        }

        $this->coverImageId = $coverImage->getId();
        return $this;
    }
}
