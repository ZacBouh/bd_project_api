<?php

namespace App\Controller;

use App\ArgumentResolver\ArtistPayloadResolver;
use App\Entity\Artist;
use App\Repository\ArtistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\SkillRepository;
use App\Service\ArtistManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class ArtistController extends AbstractController
{
    public function __construct(
        private SkillRepository $skillRepository,
        private ArtistManagerService $artistManager,
        private ArtistRepository $artistRepository,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/skills', name: 'artist_skills', methods: 'GET')]
    public function getSkills(): JsonResponse
    {
        $skills = $this->skillRepository->findAll();
        return $this->json(["skills" => array_map(fn($skill) => $skill->getName(), $skills)]);
    }

    #[Route('/api/artists', name: 'artist_create', methods: 'POST')]
    public function createArtist(
        // #[MapRequestPayload(resolver: ArtistPayloadResolver::class)] Artist $newArtist,
        Request $request
    ): JsonResponse {
        $this->logger->warning("Received Create Artist Request");
        $newArtist = $this->artistManager->createArtist($request->request, $request->files);
        return $this->json($newArtist);
    }

    #[Route('/api/artists', name: 'artist_get', methods: 'GET')]
    public function getArtists(): JsonResponse
    {
        // $artists = $this->artistManager->getAll();
        $artists = $this->artistRepository->findWithAllRelations();
        return $this->json($artists);
    }
}
