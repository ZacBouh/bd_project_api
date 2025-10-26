<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\Title\TitleDTOFactory;
use App\DTO\Title\TitleReadDTO;
use App\DTO\Title\TitleWriteDTO;
use App\Entity\Title;
use App\Service\TitleManagerService;
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
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class TitleController extends AbstractController
{
    use HardDeleteRequestTrait;

    public function __construct(
        private TitleManagerService $titleManagerService,
        private TitleDTOFactory $dtoFactory,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/titles', name: 'titles_create', methods: 'POST')]
    #[OA\Post(
        summary: 'Crée un nouveau titre',
        tags: ['Titles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: TitleWriteDTO::class))
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Titre créé',
                content: new OA\JsonContent(ref: new Model(type: TitleReadDTO::class))
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Requête invalide ou données incomplètes',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Validation failed'
                        ),
                        new OA\Property(
                            property: 'errors',
                            type: 'object',
                            additionalProperties: new OA\AdditionalProperties(
                                new OA\Schema(
                                    type: 'array',
                                    items: new OA\Items(type: 'string')
                                )
                            ),
                            example: ['isbn' => ['The provided isbn is not in a valid format']]
                        ),
                    ]
                )
            )
        ]
    )]
    public function createTitle(
        Request $request
    ): JsonResponse {
        $this->logger->warning("Received Create Title Request");
        try {
            $newTitle = $this->titleManagerService->createTitle($request->request, $request->files);
        } catch (ValidationFailedException $exception) {
            $this->logger->warning('Title creation failed due to validation error', ['errors' => (string) $exception]);

            $errors = [];
            foreach ($exception->getViolations() as $violation) {
                $propertyPath = $violation->getPropertyPath() !== '' ? $violation->getPropertyPath() : 'general';
                $errors[$propertyPath][] = $violation->getMessage();
            }

            return $this->json([
                'message' => 'Validation failed',
                'errors' => $errors,
            ], Response::HTTP_BAD_REQUEST);
        }

        $dto = $this->dtoFactory->readDTOFromEntity($newTitle);

        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/api/titles/update', name: 'titles_update', methods: 'POST')]
    #[OA\Post(
        summary: 'Mettre à jour un titre',
        tags: ['Titles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(ref: new Model(type: TitleWriteDTO::class))
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Titre mis à jour',
                content: new OA\JsonContent(ref: new Model(type: TitleReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Titre introuvable.')
        ]
    )]
    public function updateTitle(Request $request): JsonResponse
    {
        try {
            $title = $this->titleManagerService->updateTitle($request->request, $request->files);
        } catch (ValidationFailedException $exception) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => (string) $exception,
            ], Response::HTTP_BAD_REQUEST);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->dtoFactory->readDTOFromEntity($title);
        return $this->json($dto);
    }

    #[Route('/api/titles', name: 'titles_get', methods: 'GET')]
    #[OA\Get(
        summary: 'Liste tous les titres disponibles',
        tags: ['Titles'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Collection de titres',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TitleReadDTO::class))
                )
            )
        ]
    )]
    public function getTitles(): JsonResponse
    {
        $this->logger->critical("Received Get Titles Request");
        /** @var Array<int, Title> $titles */
        $titles = $this->titleManagerService->getTitles();
        return $this->json($titles);
    }

    #[Route('/api/titles', name: 'titles_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer un titre',
        tags: ['Titles'],
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
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant du titre à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Titre supprimé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Titre introuvable.')
        ]
    )]
    public function removeTitle(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $titleId */
            $titleId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($titleId)) {
                throw new InvalidArgumentException('The id is null');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning(sprintf('Attempting to remove title with id : %d', $titleId));
            $this->titleManagerService->removeTitle((int) $titleId, $hardDelete);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'Title successfully removed, id : ' . $titleId]);
    }

    #[Route('/api/titles/search', name: 'title_search', methods: 'GET')]
    #[OA\Get(
        summary: 'Recherche des titres via une requête textuelle',
        tags: ['Titles'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                description: 'Terme à rechercher',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Nombre maximum de résultats à retourner',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 200, minimum: 1)
            ),
            new OA\Parameter(
                name: 'offset',
                description: 'Décalage de pagination',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Résultats paginés de la recherche',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TitleReadDTO::class))
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Requête invalide'
            )
        ]
    )]
    public function searchTitle(
        Request $request,
    ): JsonResponse {
        $query = $request->query->getString('q');
        $limit = $request->query->getInt('limit', 200);
        $offset = $request->query->getInt('offset', 0);
        $result = $this->titleManagerService->searchTitle($query, $limit, $offset);
        return $this->json($result);
    }

    #[Route('/api/title', name: 'title_get', methods: 'POST')]
    #[OA\Post(
        summary: 'Récupère une liste de titres depuis leurs identifiants',
        tags: ['Titles'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                required: ['titleIds'],
                properties: [
                    new OA\Property(
                        property: 'titleIds',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Titres correspondants',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: TitleReadDTO::class))
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Requête invalide'
            )
        ]
    )]
    public function getTitlesFromIds(Request $request): JsonResponse
    {
        try {
            $content = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            return $this->json(['message' => 'Invalid JSON payload'], Response::HTTP_BAD_REQUEST);
        }
        if (!array_key_exists('titleIds', $content) || !is_array($content['titleIds'])) {
            return $this->json(['message' => 'Invalid request body'], Response::HTTP_BAD_REQUEST);
        }

        $titleIds = array_map('intval', $content['titleIds']);
        $titles = $this->titleManagerService->findTitles($titleIds);
        $dtos = array_map(fn(Title $title) => $this->dtoFactory->readDTOFromEntity($title), $titles);
        return $this->json($dtos);
    }
}
