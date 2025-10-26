<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\Series\SeriesDTOFactory;
use App\DTO\Series\SeriesReadDTO;
use App\Service\SeriesManagerService;
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

final class SeriesController extends AbstractController
{
    use HardDeleteRequestTrait;

    public function __construct(
        private LoggerInterface $logger,
        private SeriesManagerService $seriesService,
    ) {}

    #[Route('/api/series', name: 'series_create', methods: 'POST')]
    #[OA\Post(
        summary: 'Créer une série',
        tags: ['Series'],
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
                        new OA\Property(property: 'language', type: 'string', description: 'Code de l’énumération Language.'),
                        new OA\Property(
                            property: 'titlesId',
                            type: 'array',
                            items: new OA\Items(type: 'integer'),
                            nullable: true
                        ),
                        new OA\Property(property: 'onGoingStatus', type: 'string', nullable: true, description: 'Valeur de l’énumération OnGoingStatus.'),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Série créée.',
                content: new OA\JsonContent(ref: new Model(type: SeriesReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function index(
        Request $request,
        SeriesDTOFactory $dtoFactory
    ): JsonResponse {
        $this->logger->alert("Received Series Create " . json_encode($request->request->all()) . json_encode($request->files->all()));
        $writeDTO = $dtoFactory->writeDtoFromInputBag($request->request, $request->files);
        $series = $this->seriesService->createSeries($writeDTO);
        $this->logger->debug(sprintf('Created Series id : %s name: %s ', $series->getId(), $series->getName()));
        $readDTO = $dtoFactory->readDtoFromEntity($series);
        return $this->json($readDTO);
    }

    #[Route('/api/series/update', name: 'series_update', methods: 'POST')]
    #[OA\Post(
        summary: 'Mettre à jour une série',
        tags: ['Series'],
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
                            property: 'titlesId',
                            type: 'array',
                            items: new OA\Items(type: 'integer'),
                            nullable: true
                        ),
                        new OA\Property(property: 'onGoingStatus', type: 'string', nullable: true),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Série mise à jour.',
                content: new OA\JsonContent(ref: new Model(type: SeriesReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Série introuvable.')
        ]
    )]
    public function update(
        Request $request,
        SeriesDTOFactory $dtoFactory
    ): JsonResponse {
        try {
            $writeDTO = $dtoFactory->writeDtoFromInputBag($request->request, $request->files);
            $series = $this->seriesService->updateSeries($writeDTO);
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

        $readDTO = $dtoFactory->readDtoFromEntity($series);
        return $this->json($readDTO);
    }

    #[Route('/api/series', name: 'series_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer une série',
        tags: ['Series'],
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
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant de la série à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Série supprimée.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Série introuvable.')
        ]
    )]
    public function remove(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $seriesId */
            $seriesId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($seriesId)) {
                throw new InvalidArgumentException('The id is null');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning(sprintf('Attempting to remove series with id : %d', $seriesId));
            $this->seriesService->removeSeries((int) $seriesId, $hardDelete);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'Series successfully removed, id : ' . $seriesId]);
    }

    #[Route('/api/series', name: 'series_get_all', methods: Request::METHOD_GET)]
    #[OA\Get(
        summary: 'Lister les séries',
        tags: ['Series'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des séries.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: SeriesReadDTO::class))
                )
            )
        ]
    )]
    public function getSeries(): JsonResponse
    {
        $this->logger->critical("Received Get Series Request");
        $dtos = $this->seriesService->getSeries();
        $this->logger->alert(sprintf('Returning %s SeriesReadDTO', count($dtos)));
        return $this->json($dtos, 200);
    }
}
