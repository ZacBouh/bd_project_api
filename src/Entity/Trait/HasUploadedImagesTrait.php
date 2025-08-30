<?php

namespace App\Entity\Trait;

use App\Entity\UploadedImage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Serializer\Attribute\Groups;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

trait HasUploadedImagesTrait
{
    /**
     * @var Collection<int, UploadedImage>
     */
    #[Groups(['title:read', 'artist:read'])]
    #[ORM\ManyToMany(targetEntity: UploadedImage::class)]
    private Collection $uploadedImages;

    #[Groups(['title:read', 'artist:read', 'publisher:read'])]
    #[ORM\ManyToOne(targetEntity: UploadedImage::class, cascade: ['persist'])]
    private ?UploadedImage $coverImage = null;

    /**
     * @return Collection<int, UploadedImage>
     */
    public function getUploadedImages(): Collection
    {
        if (!isset($this->uploadedImages)) {
            $this->uploadedImages = new ArrayCollection();
        }
        return $this->uploadedImages;
    }
    /**
     * @param Collection<int, UploadedImage>|null $uploadedImages
     */
    public function setUploadedImages(?Collection $uploadedImages): static
    {
        $this->uploadedImages = $uploadedImages ?? new ArrayCollection();
        return $this;
    }

    public function addUploadedImage(UploadedImage $uploadedImage): static
    {

        if (!$this->getUploadedImages()->contains($uploadedImage)) {
            $this->uploadedImages->add($uploadedImage);
        }

        return $this;
    }

    public function removeUploadedImage(UploadedImage | int $image): static
    {
        if ($image instanceof UploadedImage) {
            $this->getUploadedImages()->removeElement($image);
            if ($this->coverImage === $image) $this->coverImage = null;
            return $this;
        }

        if (!is_null($this->coverImage)) {
            if ($this->coverImage->getId() === $image) $this->coverImage = null;
            $image = $this->getUploadedImageById($image);
            if (is_null($image)) return $this;
            $this->getUploadedImages()->removeElement($image);
            return $this;
        }

        throw new InvalidArgumentException(static::class . " removeUploadedImage accepts only int or " . UploadedImage::class . "as argument" . get_debug_type($image) . " given");
    }

    public function getCoverImage(): ?UploadedImage
    {

        return $this->coverImage;
    }

    public function getUploadedImageById(int $imageId): ?UploadedImage
    {
        foreach ($this->getUploadedImages() as $image) {
            if ($image->getId() === $imageId) {
                return $image;
            }
        }
        return null;
    }

    public function setCoverImage(UploadedImage $coverImage): static
    {
        $this->addUploadedImage($coverImage);
        $this->coverImage = $coverImage;
        return $this;
    }
}
