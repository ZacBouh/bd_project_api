<?php

namespace App\Controller;

use App\DTO\Artist\ArtistDTOFactory;
use App\DTO\Artist\ArtistReadDTOBuilder;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SkillRepository;
use App\Service\ArtistManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ArtistController extends AbstractController
{
    public function __construct(
        private SkillRepository $skillRepository,
        private ArtistManagerService $artistManager,
        private ArtistRepository $artistRepository,
        private LoggerInterface $logger,
        private ArtistDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/skills', name: 'artist_skills', methods: 'GET')]
    public function getSkills(): JsonResponse
    {
        $skills = $this->skillRepository->findAll();
        return $this->json(["skills" => array_map(fn($skill) => $skill->getName(), $skills)]);
    }

    #[Route('/api/artists', name: 'artist_create', methods: 'POST')]
    public function createArtist(
        Request $request
    ): JsonResponse {
        $this->logger->critical("Received Create Artist Request");
        $newArtist = $this->artistManager->createArtist($request->request, $request->files);
        return $this->json($newArtist);
    }

    #[Route('/api/artists', name: 'artist_get', methods: 'GET')]
    public function getArtists(): JsonResponse
    {
        $this->logger->critical("Received Get Artists Request");
        $artistsEntities = $this->artistRepository->findWithAllRelations();
        $data = [];
        foreach ($artistsEntities as $artist) {
            $data[] = $this->dtoFactory->readDtoFromEntity($artist);
        }
        return $this->json($data);
    }
}
