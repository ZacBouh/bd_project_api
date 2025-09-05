<?php

namespace App\Controller;

use App\DTO\Series\SeriesDTOFactory;
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
        private SeriesManagerService $seriesService,
    ) {}

    #[Route('/api/series', name: 'series_create', methods: 'POST')]
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
    public function getSeries(): JsonResponse
    {
        $this->logger->critical("Received Get Series Request");
        $dtos = $this->seriesService->getSeries();
        $this->logger->alert(sprintf('Returning %s SeriesReadDTO', count($dtos)));
        return $this->json($dtos, 200);
    }
}
