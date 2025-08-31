<?php

namespace App\Contract\Entity;

use App\Entity\UploadedImage;
use Doctrine\Common\Collections\Collection;

interface HasUploadedImagesInterface
{
    /**
     * @return Collection<int, UploadedImage>
     */
    public function getUploadedImages(): Collection;

    /**
     *  @param Collection<int, UploadedImage> $uploadedImages
     */
    public function setUploadedImages(Collection $uploadedImages): static;

    public function addUploadedImage(UploadedImage $uploadedImage): static;

    /**
     * @param UploadedImage|int $image
     * @return static
     */
    public function removeUploadedImage(UploadedImage|int $image): static;

    public function getCoverImage(): ?UploadedImage;

    public function setCoverImage(UploadedImage $coverImage): static;

    public function getUploadedImageById(int $imageId): ?UploadedImage;
}
