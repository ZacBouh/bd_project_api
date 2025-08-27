<?php

namespace App\Controller;

use App\DTO\PublisherCollection\PublisherCollectionDTOBuilder;
use App\Service\PublisherCollectionService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\DTO\PublisherCollection\PublisherCollectionReadDTO;

final class PublisherCollectionController extends AbstractController
{
    public function __construct(
        private PublisherCollectionService $collectionService,
        private LoggerInterface $logger,
        private PublisherCollectionDTOBuilder $dtoBuilder,
    ) {}

    #[Route('/api/collections', name: 'publisherCollection_create', methods: Request::METHOD_POST)]
    public function createPublisherCollection(
        Request $request
    ): JsonResponse {

        $entity = $this->collectionService->createPublisherCollection($request->request, $request->files);
        $dto = $this->dtoBuilder->readDTOFromEntity($entity)->buildReadDTO();
        return $this->json($dto);
    }

    #[Route('/api/collections', name: 'publisherCollection_get_all', methods: Request::METHOD_GET)]
    public function getPublisherCollections(): JsonResponse
    {
        $dtos = $this->collectionService->getPublisherCollections();
        $this->logger->alert(sprintf('Returning %s PublisherCollectionDTOs', count($dtos)));
        return $this->json($dtos, 200);
    }
}
