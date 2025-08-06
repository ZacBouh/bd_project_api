<?php

namespace App\Controller;

use App\Entity\Title;
use App\Service\TitleManagerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class TitleController extends AbstractController
{
    public function __construct(
        private TitleManagerService $titleManagerService,
        private LoggerInterface $logger
    ) {}

    #[Route('/api/titles', name: 'title_create', methods: 'POST')]
    public function createTitle(
        Request $request
    ): JsonResponse {
        $this->logger->warning("Received Create Title Request");
        $newTitle = $this->titleManagerService->createTitle($request->request, $request->files);

        return $this->json($newTitle);
    }

    #[Route('/api/titles', name: 'title_get')]
    public function getTitles(): JsonResponse
    {
        /** @var Array<int, Title> $titles */
        $titles = $this->titleManagerService->getTitles();
        return $this->json($titles);
    }
}
