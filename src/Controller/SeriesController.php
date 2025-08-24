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
        $this->seriesService->createSeries($writeDTO);
        return $this->json($writeDTO);
    }
}
