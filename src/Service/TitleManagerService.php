<?php

namespace App\Service;

use App\DTO\Title\TitleDTOBuilder;
use App\DTO\Title\TitleDTOFactory;
use App\Entity\Title;
use App\Repository\TitleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use App\DTO\Title\TitleReadDTO;
use App\Entity\Artist;
use App\Entity\ArtistTitleContribution;
use App\Entity\Skill;
use App\Mapper\TitleEntityMapper;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TitleManagerService
{
    public function __construct(
        private TitleRepository $titleRepository,
        private UploadedImageService $imageService,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator,
        private TitleEntityMapper $titleMapper,
        private TitleDTOFactory $dtoFactory,
        private LoggerInterface $logger
    ) {}

    /**
     * @param InputBag<scalar> $newTitleContent
     */
    public function createTitle(InputBag $newTitleContent, FileBag $files): Title
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($newTitleContent, $files);
        $violations = $this->validator->validate($dto);
        if (count($violations)) {
            throw new ValidationFailedException($dto, $violations);
        }
        $coverImage = null;
        $extra = [];
        if ($dto->coverImageFile !== null) {
            $coverImage = $this->imageService->saveUploadedImage($dto->coverImageFile, $dto->name . ' Cover');
            $extra['coverImage'] = $coverImage;
        }
        $title = $this->titleMapper->fromWriteDTO($dto, null, $extra);
        $this->em->persist($title);
        $this->em->flush();
        $contributions = $dto->artistsContributions;
        if (!is_null($contributions)) {
            foreach ($contributions as $contribution) {
                foreach ($contribution['skills'] as $skill) {
                    $skillRef = $this->em->getReference(Skill::class, $skill);
                    $artistRef = $this->em->getReference(Artist::class, $contribution['artist']);
                    if (is_null($skillRef) || is_null($artistRef)) {
                        throw new InvalidArgumentException(sprintf('Could not create %s with %s', ArtistTitleContribution::class, json_encode($contribution)));
                    }
                    $newContrib = new ArtistTitleContribution();
                    $newContrib->setSkill($skillRef);
                    $newContrib->setArtist($artistRef);
                    $newContrib->setTitle($title);
                    $this->em->persist($newContrib);
                    $title->addArtistsContribution($newContrib);
                }
            }
        }
        $this->em->flush();
        return $title;
    }

    /**
     * @return TitleReadDTO[]
     */
    public function getTitles(): array
    {
        $titles = [];
        $entities = $this->titleRepository->findWithAllRelations();
        foreach ($entities as $title) {
            $titles[] = $this->dtoFactory->readDTOFromEntity($title);
        }
        return $titles;
    }


    /**
     * @return array<TitleReadDTO>
     */
    public function searchTitle(string $query, int $limit = 200, int $offset = 0): array
    {
        $this->logger->debug(sprintf("Searching titles with query $query"));
        if (trim($query, " \n\r\t\v\0") == "") {
            throw new InvalidArgumentException("Cannot search title with an empty string as query");
        }
        $queryWords = preg_split('/\s+/', trim($query));
        if ($queryWords === false) {
            throw new InvalidArgumentException('The query does not contain any valid word');
        }
        $queryWords = array_filter($queryWords); // to drop empty values
        $query = implode(' ', array_map(fn($word) => "+$word*", $queryWords));

        $result = $this->titleRepository->searchTitle($query, $limit, $offset);
        $titles = [];
        foreach ($result as $title) {
            $titles[] = $this->dtoFactory->readDTOFromEntity($title);
        }
        $this->logger->debug(sprintf("Found %s title with search", count($result)));
        return $titles;
    }
}
