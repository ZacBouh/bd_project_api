<?php

namespace App\Service;

use App\DTO\Title\TitleDTOBuilder;
use App\Entity\Title;
use App\Repository\TitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use App\DTO\Title\TitleReadDTO;
use App\Mapper\TitleEntityMapper;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TitleManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TitleRepository $titleRepository,
        private UploadedImageService $imageService,
        private TitleDTOBuilder $dtoBuilder,
        private ValidatorInterface $validator,
        private TitleEntityMapper $titleMapper,
    ) {}

    /**
     * @param InputBag<string> $newTitleContent
     */
    public function createTitle(InputBag $newTitleContent, FileBag $files): Title
    {
        $dto = $this->dtoBuilder->writeDTOFromInputBags($newTitleContent, $files)->buildWriteDTO();
        $violations = $this->validator->validate($dto);
        if (count($violations)) {
            throw new ValidationFailedException($dto, $violations);
        }
        $coverImage = null;
        if ($dto->coverImageFile !== null) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, $dto->name . ' Cover');
        }
        $entity = $this->titleMapper->fromWriteDTO($dto);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        return $entity;
    }

    /**
     * @return TitleReadDTO[]
     */
    public function getTitles(): array
    {
        $titles = [];
        $entities = $this->titleRepository->findWithAllRelations();
        foreach ($entities as $title) {
            $titles[] = $this->dtoBuilder->readDTOFromEntity($title)->buildReadDTO();
        }
        return $titles;
    }
}
