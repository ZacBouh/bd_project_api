<?php

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Skill;
use App\Entity\UploadedImage;
use App\Repository\ArtistRepository;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

class ArtistManagerService
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private SkillRepository $skillRepository,
        private LoggerInterface $logger,
        private ArtistRepository $artistRepository
    ) {}

    public function createArtist(InputBag $newArtistContent, ?FileBag $files): Artist
    {
        $newArtist = new Artist();
        $newArtist->setFirstName($newArtistContent->get('firstName'));
        $newArtist->setLastName($newArtistContent->get('lastName'));
        $newArtist->setBirthDate($newArtistContent->get('birthDate'));
        $newArtist->setDeathDate($newArtistContent->get('deathDate'));
        $newArtist->setPseudo($newArtistContent->get('pseudo'));
        $skills = $this->skillRepository->findBy(['name' => $newArtistContent->all('skills')]);
        foreach ($skills as $skill) {
            $newArtist->addSkill($skill);
        }
        if (!is_null($files)) {
            $uploadedFile = $files->get('coverImageFile');
            if ($uploadedFile instanceof UploadedFile) {
                $this->logger->warning("THERE IS A COVER IMAGE");
                $coverImage = new UploadedImage();
                $coverImage->setFile($uploadedFile);
                $coverImage->setImageName("Artist Picture");

                $newArtist->setCoverImage($coverImage);
                $this->entityManager->persist($coverImage);
            }
        }

        $this->entityManager->persist($newArtist);
        $this->entityManager->flush($newArtist);

        return $newArtist;
    }

    public function getAll(): array
    {
        /** @var Array<int, Artist> $artists */
        $artists = $this->artistRepository->findBy([], limit: 200);
        return $artists;
    }
}
