<?php

namespace App\Service;

use App\DTO\Title\TitleDTOFactory;
use App\Entity\ArtistTitleContribution;
use App\Entity\Title;
use App\Repository\ArtistRepository;
use App\Repository\PublisherRepository;
use App\Repository\SkillRepository;
use App\Repository\TitleRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Serializer\SerializerInterface;
use App\DTO\Title\TitleReadDTO;

class TitleManagerService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TitleRepository $titleRepository,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
        private PublisherRepository $publisherRepository,
        private ArtistRepository $artistRepository,
        private SkillRepository $skillRepository,
        private UploadedImageService $imageService,
        private TitleDTOFactory $dtoFactory,
    ) {}

    public function createTitle(InputBag $newTitleContent, ?FileBag $files = null)
    {
        $newTitle = new Title();
        $newTitle->setDescription($newTitleContent->get('description'));
        $newTitle->setLanguage($newTitleContent->get('language'));
        $newTitle->setName($newTitleContent->get('name'));
        $newTitle->setReleaseDate(new DateTime($newTitleContent->get('releaseDate')));
        $publisher = $this->publisherRepository->findOneBy(['id' => $newTitleContent->get('publisher')]);
        $newTitle->setPublisher($publisher);

        $this->entityManager->persist($newTitle);

        foreach ($newTitleContent->all('artistsContributions') as $contributionData) {
            $artist = $this->artistRepository->findOneBy(['id' => $contributionData['artist']]);
            $skills = $this->skillRepository->findBy(['name' => $contributionData['skills']]);
            foreach ($skills as $skill) {
                $contribution = new ArtistTitleContribution();
                $contribution->setTitle($newTitle);
                $contribution->setArtist($artist);
                $contribution->setSkill($skill);
                $newTitle->addArtistsContribution($contribution);
                $this->entityManager->persist($contribution);
            }
        }

        if (!is_null($files)) {
            $this->imageService->saveUploadedCoverImage($newTitle, $files, "Cover");
        }

        $this->entityManager->flush();
        return $newTitle;
    }

    /**
     * @return TitleReadDTO[]
     */
    public function getTitles(): array
    {
        $titles = [];
        /** @var Array<int, Title> $titles */
        $entities = $this->titleRepository->findWithAllRelations();
        foreach ($entities as $title) {
            $titles[] = $this->dtoFactory->createFromEntity($title);
        }
        return $titles;
    }
}
