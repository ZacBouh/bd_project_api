<?php

namespace App\Service;

use App\Entity\ArtistTitleContribution;
use App\Entity\Title;
use App\Entity\UploadedImage;
use App\Repository\ArtistRepository;
use App\Repository\PublisherRepository;
use App\Repository\SkillRepository;
use App\Repository\TitleRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
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
        private UploadedImageService $imageService,
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
            $this->imageService->saveUploadedCoverImage($newTitle, $files, "Cover")
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
