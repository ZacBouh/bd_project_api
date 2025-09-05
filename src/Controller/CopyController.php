<?php

namespace App\Controller;

use App\DTO\Copy\CopyDTOBuilder;
use App\DTO\Copy\CopyReadDTO;
use App\DTO\Copy\CopyWriteDTO;
use App\Service\CopyManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class CopyController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private CopyManagerService $copyService,
        private CopyDTOBuilder $copyDTOBuilder,
    ) {}

    #[Route('/api/copy', name: 'copy_create', methods: 'POST')]
    public function createCopy(
        Request $request,
    ): JsonResponse {
        $this->logger->warning('Received Create Copy Request');

        $createdCopy = $this->copyService->createCopy($request->request, $request->files);
        $dto = $this->copyDTOBuilder->readDTOFromEntity($createdCopy)->buildReadDTO();
        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/api/copy', name: 'copy_get', methods: 'GET')]
    public function getCopies(
        Request $request,
    ): JsonResponse {
        $this->logger->critical('Received Get Copies Request');

        $data = $this->copyService->getCopies();

        return $this->json($data);
    }

    #[Route('/api/copy', name: 'copy_remove', methods: 'DELETE')]
    public function removeCopy(
        #[MapRequestPayload] CopyWriteDTO $copyDTO
    ): JsonResponse {
        try {
            $this->copyService->removeCopy($copyDTO);
            return $this->json(['message' => 'Copy successfully removed, id : ' . $copyDTO->id]);
        } catch (\Exception $e) {
            return $this->json(['message' => 'error' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR]);
        }
    }

    #[Route('/api/copy/update', name: 'copy_update', methods: 'POST')]
    public function updateCopy(
        Request $request
    ): JsonResponse {

        $updatedCopy = $this->copyService->updateCopy($request->request, $request->files);
        return $this->json($updatedCopy);
    }
}
