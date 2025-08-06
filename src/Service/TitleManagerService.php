<?php

namespace App\Service;

use App\Entity\ArtistTitleContribution;
use App\Entity\Title;
use App\Repository\ArtistRepository;
use App\Repository\PublisherRepository;
use App\Repository\SkillRepository;
use App\Repository\TitleRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;

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
    ) {}

    public function createTitle(array $newTitleContent)
    {
        $newTitle = new Title();
        $newTitle->setDescription($newTitleContent['description']);
        $newTitle->setLanguage($newTitleContent['language']);
        $newTitle->setName($newTitleContent['name']);
        $newTitle->setReleaseDate(new DateTime($newTitleContent['releaseDate']));
        $publisher = $this->publisherRepository->findOneBy(['id' => $newTitleContent['publisher']]);
        $newTitle->setPublisher($publisher);
        $this->logger->warning("Deserialized data : " . $publisher->getName());

        foreach ($newTitleContent['artistsContributions'] as $contributionData) {
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
        $this->entityManager->persist($newTitle);
        $this->entityManager->flush();
        return $newTitle;
    }

    public function getTitles(): array
    {
        /** @var Array<int, Title> $titles */
        $titles = $this->titleRepository->findBy([], limit: 200);
        return $titles;
    }
}
