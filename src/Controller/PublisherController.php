<?php

namespace App\Controller;

use App\DTO\Publisher\PublisherDTOFactory;
use App\DTO\Publisher\PublisherReadDTO;
use App\Entity\Publisher;
use App\Service\PublisherManagerService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublisherController extends AbstractController
{
    public function __construct(
        private PublisherManagerService $publisherManagerService,
        private LoggerInterface $logger,
        private PublisherDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/publishers', name: 'publishers_get', methods: 'GET')]
    #[OA\Get(
        summary: 'Lister les éditeurs',
        tags: ['Publishers'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste complète des éditeurs.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PublisherReadDTO::class))
                )
            )
        ]
    )]
    public function getPublishers(): JsonResponse
    {
        $this->logger->critical("Received Get Publishers Request");
        /** @var Array<int, Publisher> $publishers */
        $publishers = $this->publisherManagerService->getPublishers();
        return $this->json($publishers);
    }

    #[Route('/api/publishers', name: 'publishers_create', methods: 'POST')]
    #[OA\Post(
        summary: 'Créer un éditeur',
        tags: ['Publishers'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(
                            property: 'titles',
                            type: 'array',
                            items: new OA\Items(type: 'integer'),
                            nullable: true
                        ),
                        new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'deathDate', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(
                            property: 'uploadedImages',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éditeur créé.',
                content: new OA\JsonContent(ref: new Model(type: PublisherReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function createPublisher(
        Request $request,
    ): JsonResponse {
        $this->logger->warning("Received Create Publisher Request");
        $newPublisher = $this->publisherManagerService->createPublisher($request->request, $request->files);
        return $this->json($newPublisher);
    }

    #[Route('/api/publishers/search', name: 'publishers_search', methods: 'GET')]
    #[OA\Get(
        summary: 'Rechercher des éditeurs',
        tags: ['Publishers'],
        parameters: [
            new OA\Parameter(name: 'q', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 200, minimum: 1)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0, minimum: 0))
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éditeurs correspondant à la recherche.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PublisherReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Paramètres de recherche invalides.')
        ]
    )]
    public function searchPublisher(
        Request $request,
    ): JsonResponse {
        $query = $request->query->getString('q');
        $limit = $request->query->getInt('limit');
        $offset = $request->query->getInt('offset');
        $publishers = $this->publisherManagerService->searchPublisher($query, $limit, $offset);
        $dtos = array_map(fn($publisher) => $this->dtoFactory->readDtoFromEntity($publisher), $publishers);
        return $this->json($dtos);
    }
}
