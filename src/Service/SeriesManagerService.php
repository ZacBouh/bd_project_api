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
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use InvalidArgumentException;
use App\Service\UploadedImageService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Security\Role;

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
        private Security $security,
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

    public function updateSeries(SeriesWriteDTO $seriesDTO): Series
    {
        $violations = $this->validator->validate($seriesDTO);
        if (count($violations) > 0) {
            throw new ValidationFailedException($seriesDTO, $violations);
        }
        if (is_null($seriesDTO->id)) {
            throw new InvalidArgumentException('Update series : id is null');
        }
        /** @var Series|null $series */
        $series = $this->seriesRepo->find($seriesDTO->id);
        if (is_null($series)) {
            throw new ResourceNotFoundException('No series was found for id ' . $seriesDTO->id);
        }
        if ($series->isDeleted()) {
            throw new ResourceNotFoundException('No series was found for id ' . $seriesDTO->id);
        }
        $extra = [];
        if ($seriesDTO->coverImageFile !== null) {
            $extra['coverImage'] = $this->imageService->saveUploadedImage($seriesDTO->coverImageFile, $seriesDTO->name . ' Image');
        }
        /** @var Series $series */
        $series = $this->seriesMapper->fromWriteDTO($seriesDTO, $series, $extra);
        $this->entityManager->persist($series);
        $this->entityManager->flush();
        return $series;
    }

    public function removeSeries(int $seriesId, bool $hardDelete = false): void
    {
        /** @var Series|null $series */
        $series = $this->seriesRepo->find($seriesId);
        if (is_null($series)) {
            throw new ResourceNotFoundException('No series was found for id ' . $seriesId);
        }
        if ($series->isDeleted() && !$hardDelete) {
            throw new ResourceNotFoundException('No series was found for id ' . $seriesId);
        }

        if ($hardDelete) {
            if (!$this->security->isGranted(Role::ADMIN->value)) {
                throw new AccessDeniedException('Hard delete requires administrator role');
            }
            $this->entityManager->remove($series);
        } else {
            $series->markAsDeleted();
            $this->entityManager->persist($series);
        }
        $this->entityManager->flush();
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
