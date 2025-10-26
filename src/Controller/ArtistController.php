<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\Artist\ArtistDTOFactory;
use App\DTO\Artist\ArtistReadDTO;
use App\Repository\SkillRepository;
use App\Service\ArtistManagerService;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class ArtistController extends AbstractController
{
    use HardDeleteRequestTrait;

    public function __construct(
        private SkillRepository $skillRepository,
        private ArtistManagerService $artistManager,
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
        try {
            $newArtist = $this->artistManager->createArtist($request->request, $request->files);
        } catch (ValidationFailedException $exception) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => (string) $exception,
            ], Response::HTTP_BAD_REQUEST);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        $dto = $this->dtoFactory->readDtoFromEntity($newArtist);
        return $this->json($dto);
    }

    #[Route('/api/artists/update', name: 'artist_update', methods: 'POST')]
    #[OA\Post(
        summary: 'Mettre à jour un artiste',
        tags: ['Artists'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id', 'skills'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
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
                description: 'Artiste mis à jour.',
                content: new OA\JsonContent(ref: new Model(type: ArtistReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Artiste introuvable.')
        ]
    )]
    public function updateArtist(Request $request): JsonResponse
    {
        try {
            $artist = $this->artistManager->updateArtist($request->request, $request->files);
        } catch (ValidationFailedException $exception) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => (string) $exception,
            ], Response::HTTP_BAD_REQUEST);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->dtoFactory->readDtoFromEntity($artist);
        return $this->json($dto);
    }

    #[Route('/api/artists', name: 'artist_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer un artiste',
        tags: ['Artists'],
        parameters: [
            new OA\Parameter(
                name: 'hardDelete',
                in: 'query',
                description: 'Forcer la suppression définitive (administrateur uniquement).',
                schema: new OA\Schema(type: 'boolean'),
                required: false
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant de l’artiste à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Artiste supprimé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Artiste introuvable.')
        ]
    )]
    public function removeArtist(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $artistId */
            $artistId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($artistId)) {
                throw new InvalidArgumentException('The id is null');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning(sprintf('Attempting to remove artist with id : %d', $artistId));
            $this->artistManager->removeArtist((int) $artistId, $hardDelete);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'Artist successfully removed, id : ' . $artistId]);
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
        $artistsDtos = $this->artistManager->getAll();
        return $this->json($artistsDtos);
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
