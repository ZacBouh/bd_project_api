<?php

namespace App\Controller;

use App\Service\CopyManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class CopyController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private CopyManagerService $copyService,
    ) {}

    #[Route('/api/copy', name: 'copy_create', methods: 'POST')]
    public function createCopy(
        Request $request,
    ): JsonResponse {
        $this->logger->warning('Received Create Copy Request');

        $createdCopy = $this->copyService->createCopy($request->request, $request->files);
        return $this->json($createdCopy);
    }

    #[Route('/api/copy', name: 'copy_get', methods: 'GET')]
    public function getCopies(
        Request $request,
    ): JsonResponse {
        $this->logger->warning('Received Get Copies Request');

        $data = $this->copyService->getCopies();

        return $this->json($data);
    }
}
