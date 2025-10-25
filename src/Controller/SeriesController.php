<?php

namespace App\Controller;

use App\DTO\Series\SeriesDTOFactory;
use App\DTO\Series\SeriesReadDTO;
use App\Service\SeriesManagerService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SeriesController extends AbstractController
{
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
                    required: ['name', 'publisher', 'language'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'name', type: 'string'),
                        new OA\Property(property: 'publisher', type: 'integer'),
                        new OA\Property(property: 'language', type: 'string', description: 'Code de l’énumération Language.'),
                        new OA\Property(
                            property: 'titles',
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
