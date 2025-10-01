<?php

namespace App\Controller;

use App\Service\ScanService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;

final class SearchController extends AbstractController
{

    public function __construct(
        private ScanService $scanService,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/scan', name: 'scan_comic_picture', methods: 'POST')]
    public function scanComicPicture(
        Request $request
    ): JsonResponse {

        /** @var User $user */
        $user = $this->getUser();
        $request->request->add(['user' => $user->getId()]);
        try {
            $data = $this->scanService->scanComicPicture($request->request, $request->files);
        } catch (InvalidArgumentException $e) {
            $message = $e->getMessage();
            $this->logger->notice($message);
            return $this->json(['success' => false, 'message' => $message], Response::HTTP_BAD_REQUEST);
        }
        $response = new JsonResponse($data, 200, json: true);
        return $response;
    }

    #[Route('/', name: 'healt_check', methods: 'GET')]
    public function healtCheck(): Response
    {
        return new Response("Server Up :)");
    }
}
