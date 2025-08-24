<?php

namespace App\Service;

use App\DTO\Series\SeriesDTOBuilder;
use App\DTO\Series\SeriesWriteDTO;
use App\Mapper\SeriesMapper;
use App\Repository\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SeriesManagerService
{
    public function __construct(
        private LoggerInterface $logger,
        private SeriesRepository $seriesRepo,
        private SeriesDTOBuilder $dtoBuilder,
        private UploadedImageService $imageService,
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private SeriesMapper $seriesMapper,
    ) {}

    public function createSeries(SeriesWriteDTO $seriesDTO)
    {
        $violations = $this->validator->validate($seriesDTO);
        if (count($violations) > 0) {
            throw new ValidationFailedException($seriesDTO, $violations);
        }
        $coverImage = null;
        if ($seriesDTO->coverImageFile !== null) {
            $coverImage = $this->imageService->saveUploadedImage($seriesDTO->coverImageFile, $seriesDTO->name . ' Image');
        }

        $series = $this->seriesMapper->fromWriteDTO($seriesDTO, $coverImage);

        $this->entityManager->persist($series);
        $this->entityManager->flush();

        $this->logger->critical('validated dto');
    }
}
