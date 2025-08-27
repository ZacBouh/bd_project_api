<?php

namespace App\Controller;

use App\DTO\Series\SeriesDTOBuilder;
use App\Service\SeriesManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class SeriesController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private SeriesDTOBuilder $seriesDTOBuilder,
        private SeriesManagerService $seriesService,
    ) {}

    #[Route('/api/series', name: 'series_create', methods: 'POST')]
    public function index(
        Request $request
    ): JsonResponse {
        $this->logger->alert("Received Series Create " . json_encode($request->request->all()) . json_encode($request->files->all()));
        $writeDTO = $this->seriesDTOBuilder->writeDTOFromInputBags($request->request, $request->files)
            ->buildWriteDTO();
        $series = $this->seriesService->createSeries($writeDTO);
        $this->logger->critical('Series : ' . $series->getId() . $series->getName());
        $readDTO = $this->seriesDTOBuilder->readDTOFromEntity($series)->buildReadDTO();
        return $this->json($readDTO);
    }

    #[Route('/api/series', name: 'series_get_all', methods: Request::METHOD_GET)]
    public function getSeries(): JsonResponse
    {
        $dtos = $this->seriesService->getSeries();
        $this->logger->alert(sprintf('Returning %s SeriesReadDTO', count($dtos)));
        return $this->json($dtos, 200);
    }
}
