<?php

namespace App\Controller;

use App\Repository\PublisherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Publisher;
use App\Service\PublisherManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class PublisherController extends AbstractController
{
    public function __construct(
        private PublisherRepository $publisherRepository,
        private PublisherManagerService $publisherManagerService,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/publishers', name: 'publishers_get', methods: 'GET')]
    public function getPublishers(): JsonResponse
    {
        /** @var Array<int, Publisher> $publishers */
        $publishers = $this->publisherRepository->findWithAllRelations();

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
}
