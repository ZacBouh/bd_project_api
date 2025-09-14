<?php

namespace App\Controller;

use App\DTO\Publisher\PublisherDTOFactory;
use App\Repository\PublisherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Publisher;
use App\Service\PublisherManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

final class PublisherController extends AbstractController
{
    public function __construct(
        private PublisherManagerService $publisherManagerService,
        private LoggerInterface $logger,
        private PublisherDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/publishers', name: 'publishers_get', methods: 'GET')]
    public function getPublishers(): JsonResponse
    {
        $this->logger->critical("Received Get Publishers Request");
        /** @var Array<int, Publisher> $publishers */
        $publishers = $this->publisherManagerService->getPublishers();
        return $this->json($publishers);
    }

    #[Route('/api/publishers', name: 'publishers_create', methods: 'POST')]
    public function createPublisher(
        Request $request,
    ): JsonResponse {
        $this->logger->warning("Received Create Publisher Request");
        $newPublisher = $this->publisherManagerService->createPublisher($request->request, $request->files);
        return $this->json($newPublisher);
    }

    #[Route('/api/publishers/search', name: 'publishers_search', methods: 'GET')]
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
