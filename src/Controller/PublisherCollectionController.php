<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\PublisherCollection\PublisherCollectionDTOFactory;
use App\DTO\PublisherCollection\PublisherCollectionReadDTO;
use App\Service\PublisherCollectionService;
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

final class PublisherCollectionController extends AbstractController
{
    use HardDeleteRequestTrait;

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
                    required: ['name', 'publisherId', 'language'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'publisherId', type: 'integer'),
                        new OA\Property(property: 'language', type: 'string', description: 'Code ISO 639-1.'),
                        new OA\Property(
                            property: 'titleIds',
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

    #[Route('/api/collections/update', name: 'publisherCollection_update', methods: Request::METHOD_POST)]
    #[OA\Post(
        summary: 'Mettre à jour une collection d’éditeur',
        tags: ['Publisher Collections'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id', 'name', 'publisherId', 'language'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'publisherId', type: 'integer'),
                        new OA\Property(property: 'language', type: 'string'),
                        new OA\Property(
                            property: 'titleIds',
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
                description: 'Collection mise à jour.',
                content: new OA\JsonContent(ref: new Model(type: PublisherCollectionReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Collection introuvable.')
        ]
    )]
    public function updatePublisherCollection(
        Request $request
    ): JsonResponse {
        try {
            $entity = $this->collectionService->updatePublisherCollection($request->request, $request->files);
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

        $dto = $this->dtoFactory->readDtoFromEntity($entity);
        return $this->json($dto);
    }

    #[Route('/api/collections', name: 'publisherCollection_remove', methods: Request::METHOD_DELETE)]
    #[OA\Delete(
        summary: 'Supprimer une collection d’éditeur',
        tags: ['Publisher Collections'],
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
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant de la collection à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Collection supprimée.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Collection introuvable.')
        ]
    )]
    public function removePublisherCollection(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $collectionId */
            $collectionId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($collectionId)) {
                throw new InvalidArgumentException('The id is null');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning(sprintf('Attempting to remove collection with id : %d', $collectionId));
            $this->collectionService->removePublisherCollection((int) $collectionId, $hardDelete);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'Collection successfully removed, id : ' . $collectionId]);
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
