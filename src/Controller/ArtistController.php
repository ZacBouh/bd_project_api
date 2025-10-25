<?php

namespace App\Controller;

use App\DTO\Artist\ArtistDTOFactory;
use App\DTO\Artist\ArtistReadDTO;
use App\Repository\ArtistRepository;
use App\Repository\SkillRepository;
use App\Service\ArtistManagerService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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
    #[OA\Get(
        summary: 'Lister les compétences disponibles',
        description: 'Retourne la liste de toutes les compétences pouvant être associées à un artiste.',
        tags: ['Artists'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des compétences.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'skills',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            )
        ]
    )]
    public function getSkills(): JsonResponse
    {
        $skills = $this->skillRepository->findAll();
        return $this->json(["skills" => array_map(fn($skill) => $skill->getName(), $skills)]);
    }

    #[Route('/api/artists', name: 'artist_create', methods: 'POST')]
    #[OA\Post(
        summary: 'Créer un nouvel artiste',
        tags: ['Artists'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['skills'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'firstName', type: 'string', nullable: true),
                        new OA\Property(property: 'lastName', type: 'string', nullable: true),
                        new OA\Property(property: 'pseudo', type: 'string', nullable: true),
                        new OA\Property(
                            property: 'skills',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            description: 'Identifiants ou noms de compétences enregistrées.'
                        ),
                        new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'deathDate', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Artiste créé avec succès.',
                content: new OA\JsonContent(ref: new Model(type: ArtistReadDTO::class))
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Requête invalide ou violation des contraintes.'
            )
        ]
    )]
    public function createArtist(
        Request $request
    ): JsonResponse {
        $this->logger->critical("Received Create Artist Request");
        $newArtist = $this->artistManager->createArtist($request->request, $request->files);
        return $this->json($newArtist);
    }

    #[Route('/api/artists', name: 'artist_get', methods: 'GET')]
    #[OA\Get(
        summary: 'Lister les artistes',
        tags: ['Artists'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des artistes avec leurs informations principales.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: ArtistReadDTO::class))
                )
            )
        ]
    )]
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
    #[OA\Get(
        summary: 'Rechercher des artistes',
        tags: ['Artists'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: true,
                description: 'Termes recherchés (séparés par des espaces).',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 200, minimum: 1)
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Résultats paginés de la recherche.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: ArtistReadDTO::class))
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'La requête de recherche est invalide.'
            )
        ]
    )]
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
