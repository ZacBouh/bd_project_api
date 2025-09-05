<?php

namespace App\Service;

use App\DTO\Series\SeriesDTOBuilder;
use App\DTO\Series\SeriesDTOFactory;
use App\DTO\Series\SeriesWriteDTO;
use App\DTO\Series\SeriesReadDTO;
use App\Entity\Series;
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
        private UploadedImageService $imageService,
        private ValidatorInterface $validator,
        private EntityManagerInterface $entityManager,
        private SeriesMapper $seriesMapper,
        private SeriesDTOFactory $dtoFactory,
    ) {}

    public function createSeries(SeriesWriteDTO $seriesDTO): Series
    {
        $violations = $this->validator->validate($seriesDTO);
        if (count($violations) > 0) {
            throw new ValidationFailedException($seriesDTO, $violations);
        }
        $extra = [];
        $coverImage = null;
        if ($seriesDTO->coverImageFile !== null) {
            $coverImage = $this->imageService->saveUploadedImage($seriesDTO->coverImageFile, $seriesDTO->name . ' Image');
            $extra['coverImage'] = $coverImage;
        }

        $series = $this->seriesMapper->fromWriteDTO($seriesDTO, extra: $extra);

        $this->entityManager->persist($series);
        $this->entityManager->flush();

        $this->logger->critical('validated dto');
        return $series;
    }

    /**
     * @return SeriesReadDTO[]
     */
    public function getSeries(): array
    {
        $seriesArray = $this->seriesRepo->findAllWithPublisherAndImages();
        $dtos = array_map(fn($series) => $this->dtoFactory->readDtoFromEntity($series), $seriesArray);
        return $dtos;
    }
}
