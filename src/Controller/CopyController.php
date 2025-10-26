<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\Copy\CopyDTOFactory;
use App\DTO\Copy\CopyReadDTO;
use App\Service\CopyManagerService;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CopyController extends AbstractController
{
    use HardDeleteRequestTrait;

    public function __construct(
        private LoggerInterface $logger,
        private CopyManagerService $copyService,
        private CopyDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/copy', name: 'copy_create', methods: 'POST')]
    #[OA\Post(
        summary: 'Créer un exemplaire',
        tags: ['Copies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['ownerId', 'titleId', 'copyCondition'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'ownerId', type: 'integer'),
                        new OA\Property(property: 'titleId', type: 'integer'),
                        new OA\Property(property: 'copyCondition', type: 'string', description: 'Valeur de l’énumération CopyCondition.'),
                        new OA\Property(property: 'price', type: 'integer', nullable: true, description: 'Prix en centimes.'),
                        new OA\Property(property: 'currency', type: 'string', nullable: true, description: 'Code devise PriceCurrency.'),
                        new OA\Property(property: 'boughtForPrice', type: 'integer', nullable: true, description: 'Prix d\'achat en centimes.'),
                        new OA\Property(property: 'boughtForCurrency', type: 'string', nullable: true, description: 'Code devise PriceCurrency.'),
                        new OA\Property(property: 'forSale', type: 'boolean', nullable: true),
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
                description: 'Exemplaire créé.',
                content: new OA\JsonContent(ref: new Model(type: CopyReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function createCopy(
        Request $request,
    ): JsonResponse {
        $this->logger->warning('Received Create Copy Request');

        $createdCopy = $this->copyService->createCopy($request->request, $request->files);
        $dto = $this->dtoFactory->readDTOFromEntity($createdCopy);
        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/api/copy', name: 'copy_get', methods: 'GET')]
    #[OA\Get(
        summary: 'Lister les exemplaires',
        tags: ['Copies'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste de tous les exemplaires.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: CopyReadDTO::class))
                )
            )
        ]
    )]
    public function getCopies(
        Request $request,
    ): JsonResponse {
        $this->logger->critical('Received Get Copies Request');

        $data = $this->copyService->getCopies();

        return $this->json($data);
    }

    #[Route('/api/copy', name: 'copy_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer un exemplaire',
        tags: ['Copies'],
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
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant de l’exemplaire à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Exemplaire supprimé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Erreur lors de la suppression.')
        ]
    )]
    public function removeCopy(
        Request $request
    ): JsonResponse {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $copyId */
            $copyId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($copyId)) {
                throw new InvalidArgumentException('The id is null ');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning("Intenting to remove copy with id : $copyId");
            $this->copyService->removeCopy($copyId, $hardDelete);
            return $this->json(['message' => 'Copy successfully removed, id : ' . $copyId]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'error' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR]);
        }
    }

    #[Route('/api/copy/update', name: 'copy_update', methods: 'POST')]
    #[OA\Post(
        summary: 'Mettre à jour un exemplaire',
        tags: ['Copies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'ownerId', type: 'integer', nullable: true),
                        new OA\Property(property: 'titleId', type: 'integer', nullable: true),
                        new OA\Property(property: 'copyCondition', type: 'string', nullable: true),
                        new OA\Property(property: 'price', type: 'integer', nullable: true, description: 'Prix en centimes.'),
                        new OA\Property(property: 'currency', type: 'string', nullable: true),
                        new OA\Property(property: 'boughtForPrice', type: 'integer', nullable: true, description: 'Prix d\'achat en centimes.'),
                        new OA\Property(property: 'boughtForCurrency', type: 'string', nullable: true),
                        new OA\Property(property: 'forSale', type: 'boolean', nullable: true),
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
                description: 'Exemplaire mis à jour.',
                content: new OA\JsonContent(ref: new Model(type: CopyReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function updateCopy(
        Request $request
    ): JsonResponse {
        $updatedCopy = $this->copyService->updateCopy($request->request, $request->files);
        $dto = $this->dtoFactory->readDtoFromEntity($updatedCopy);
        return $this->json($dto);
    }

    #[Route('/api/copy/search', name: 'copy_search', methods: 'GET')]
    #[OA\Get(
        summary: 'Rechercher des exemplaires',
        tags: ['Copies'],
        parameters: [
            new OA\Parameter(name: 'query', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 200, minimum: 1)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)),
            new OA\Parameter(name: 'forSale', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Résultats de la recherche.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: CopyReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Paramètres de recherche invalides.')
        ]
    )]
    public function searchCopy(
        Request $request
    ): JsonResponse {
        try {

            $user = $this->getUser();
            $query = $request->query->getString('query');
            $limit = $request->query->getInt('limit', 200);
            $offset = $request->query->getInt('offset', 0);
            $forSale = $request->query->get('forSale', null) ? $request->query->getBoolean('forSale') : null;
            $this->logger->debug("Copy search request : $query limit: $limit offset: $offset, forSale: $forSale");
            $copies = $this->copyService->searchCopy($query, $limit, $offset, $forSale);
            $this->logger->debug(sprintf("Found %s copies for query $query", count($copies)));
            $dtos = [];
            foreach ($copies as $copy) {
                $dtos[] = $this->dtoFactory->readDtoFromEntity($copy);
            }
            return $this->json($dtos);
        } catch (InvalidArgumentException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (BadRequestException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
