<?php

namespace App\Controller;

use App\DTO\PublisherCollection\PublisherCollectionDTOFactory;
use App\DTO\PublisherCollection\PublisherCollectionReadDTO;
use App\Service\PublisherCollectionService;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublisherCollectionController extends AbstractController
{
    public function __construct(
        private PublisherCollectionService $collectionService,
        private LoggerInterface $logger,
        private PublisherCollectionDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/collections', name: 'publisherCollection_create', methods: Request::METHOD_POST)]
    #[OA\Post(
        summary: 'Créer une collection d’éditeur',
        tags: ['Publisher Collections'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['name', 'publisher', 'language'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'publisher', type: 'integer'),
                        new OA\Property(property: 'language', type: 'string', description: 'Code ISO 639-1.'),
                        new OA\Property(
                            property: 'titles',
                            type: 'array',
                            items: new OA\Items(type: 'integer'),
                            nullable: true
                        ),
                        new OA\Property(property: 'description', type: 'string', nullable: true),
                        new OA\Property(property: 'birthDate', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'deathDate', type: 'string', format: 'date', nullable: true),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Collection créée.',
                content: new OA\JsonContent(ref: new Model(type: PublisherCollectionReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function createPublisherCollection(
        Request $request
    ): JsonResponse {

        $entity = $this->collectionService->createPublisherCollection($request->request, $request->files);
        $dto = $this->dtoFactory->readDtoFromEntity($entity);
        return $this->json($dto);
    }

    #[Route('/api/collections', name: 'publisherCollection_get_all', methods: Request::METHOD_GET)]
    #[OA\Get(
        summary: 'Lister les collections d’éditeur',
        tags: ['Publisher Collections'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des collections.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: PublisherCollectionReadDTO::class))
                )
            )
        ]
    )]
    public function getPublisherCollections(): JsonResponse
    {
        $this->logger->critical("Received Get PubliserCollections Request");
        $dtos = $this->collectionService->getPublisherCollections();
        $this->logger->alert(sprintf('Returning %s PublisherCollectionDTOs', count($dtos)));
        return $this->json($dtos, 200);
    }
}
