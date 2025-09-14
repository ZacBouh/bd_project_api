<?php

namespace App\Controller;

use App\DTO\Title\TitleDTOFactory;
use App\Entity\Title;
use App\Service\TitleManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TitleController extends AbstractController
{
    public function __construct(
        private TitleManagerService $titleManagerService,
        private TitleDTOFactory $dtoFactory,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/titles', name: 'title_create', methods: 'POST')]
    public function createTitle(
        Request $request
    ): JsonResponse {
        $this->logger->warning("Received Create Title Request");
        $newTitle = $this->titleManagerService->createTitle($request->request, $request->files);

        $dto = $this->dtoFactory->readDTOFromEntity($newTitle);

        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/api/titles', name: 'title_get', methods: 'GET')]
    public function getTitles(): JsonResponse
    {
        $this->logger->critical("Received Get Titles Request");
        /** @var Array<int, Title> $titles */
        $titles = $this->titleManagerService->getTitles();
        return $this->json($titles);
    }

    #[Route('/api/titles/search', name: 'title_search', methods: 'GET')]
    public function searchTitle(
        Request $request,
    ): JsonResponse {
        $query = $request->query->getString('q');
        $limit = $request->query->getInt('limit', 200);
        $offset = $request->query->getInt('offset', 0);
        $result = $this->titleManagerService->searchTitle($query, $limit, $offset);
        return $this->json($result);
    }
}
