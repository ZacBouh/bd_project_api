<?php

namespace App\Controller;

use App\Entity\Title;
use App\Service\TitleManagerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class TitleController extends AbstractController
{
    public function __construct(
        private TitleManagerService $titleManagerService
    ) {}

    #[Route('/titles', name: 'title_create')]
    public function createTitle(
        #[MapRequestPayload] Title $title
    ): JsonResponse {

        $this->titleManagerService->createTitle($title);

        return $this->json($title);
    }
}
