<?php

namespace App\Entity;

use App\Repository\UploadedImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: UploadedImageRepository::class)]
#[Vich\Uploadable]
class UploadedImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['titleReadDTO', 'artist:read', 'uploadedImage:read'])]

    private ?int $id = null;

    #[Ignore] // This exclude the property from any serialization
    #[Vich\UploadableField(mapping: 'uploaded_image', fileNameProperty: 'fileName', size: 'fileSize', originalName: 'originalFileName')]
    private ?File $file = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(length: 255)]
    private ?string $imageName = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(nullable: true)]
    private ?int $fileSize = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(length: 255)]
    private ?string $fileName = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(length: 255)]
    private ?string $originalFileName = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileMimeType = null;

    #[Groups(['titleReadDTO', 'uploadedImage:read'])]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $imageDimensions = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFileName()
    {
        return $this->fileName;
    }
    public function setFileName(string $fileName): static
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFileMimeType()
    {
        return $this->fileMimeType;
    }
    public function setFileMimeType(string $fileMimeType): static
    {
        $this->fileMimeType = $fileMimeType;
        return $this;
    }

    public function getImageDimensions()
    {
        return $this->imageDimensions;
    }
    public function setImageDimensions(string $imageDimensions): static
    {
        $this->imageDimensions = $imageDimensions;
        return $this;
    }

    public function getOriginalFileName()
    {
        return $this->originalFileName;
    }
    public function setOriginalFileName(string $originalFileName): static
    {
        $this->originalFileName = $originalFileName;
        return $this;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageName(string $imageName): static
    {
        $this->imageName = $imageName;

        return $this;
    }

    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    public function setFileSize(?int $fileSize): static
    {
        $this->fileSize = $fileSize;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        // It is required that at least one field changes if you are using doctrine
        // otherwise the event listeners won't be called and the file is lost
        // check documentation at https://github.com/dustin10/VichUploaderBundle/blob/master/docs/usage.md
        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }
}
