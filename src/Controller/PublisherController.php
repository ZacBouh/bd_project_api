<?php

namespace App\Controller;

use App\Repository\PublisherRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Publisher;
use App\Service\PublisherManagerService;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

final class PublisherController extends AbstractController
{
    public function __construct(
        private PublisherRepository $publisherRepository,
        private PublisherManagerService $publisherManagerService
    ) {}

    #[Route('/api/publishers', name: 'publishers', methods: 'GET')]
    public function getPublishers(): JsonResponse
    {
        /** @var Array<int, Publisher> $publishers */
        $publishers = $this->publisherRepository->findBy([], limit: 200);

        return $this->json($publishers);
    }

    #[Route('/api/publishers', name: 'publishers', methods: 'POST')]
    public function createPublisher(
        #[MapRequestPayload] Publisher $publisher,
    ): JsonResponse {
        $this->publisherManagerService->createPublisher($publisher);
        return $this->json($publisher);
    }
}
