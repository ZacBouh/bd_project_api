<?php

namespace App\Controller;

use App\DTO\Artist\ArtistDTOFactory;
use App\DTO\Artist\ArtistReadDTO;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SkillRepository;
use App\Service\ArtistManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

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

    #[Route('/api/artists/search', name: 'artist_search', methods: 'GET')]
    public function searchArtist(
        Request $request,
    ): JsonResponse {
        $query = $request->query->getString('q');
        $limit = $request->query->getInt('limit');
        $offset = $request->query->getInt('offset');

        $artistsDtos = $this->artistManager->searchArtist($query, $limit, $offset);
        return $this->json($artistsDtos);
    }
}
