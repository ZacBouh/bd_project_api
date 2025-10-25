<?php

namespace App\Controller;

use App\DTO\Title\TitleDTOFactory;
use App\DTO\Title\TitleReadDTO;
use App\DTO\Title\TitleWriteDTO;
use App\Entity\Title;
use App\Service\TitleManagerService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class TitleController extends AbstractController
{
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
                            additionalProperties: new OA\Schema(
                                type: 'array',
                                items: new OA\Items(type: 'string')
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
                description: 'Aucun identifiant fourni'
            )
        ]
    )]
    public function getTitle(
        Request $request
    ): JsonResponse {
        /** @var array<string, mixed> $data  */
        $data = json_decode($request->getContent(), true);
        /** @var string[] $titleIds */
        $titleIds = $data['titleIds'] ?? [];
        if (count($titleIds) === 0) {
            return $this->json(["error" => "No titleId array provided"], Response::HTTP_BAD_REQUEST);
        }
        $titles = $this->titleManagerService->findTitles($titleIds);
        $dto = [];
        foreach ($titles as $title) {
            $dto[] = $this->dtoFactory->readDTOFromEntity($title);
        }
        return $this->json($dto);
    }
}
