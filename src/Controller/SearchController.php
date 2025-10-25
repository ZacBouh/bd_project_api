<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ScanService;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{

    public function __construct(
        private ScanService $scanService,
        private LoggerInterface $logger,
    ) {}

    #[Route('/api/scan', name: 'scan_comic_picture', methods: 'POST')]
    #[OA\Post(
        summary: 'Analyser la couverture d’un ouvrage',
        tags: ['Scan'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['user'],
                    properties: [
                        new OA\Property(property: 'user', type: 'integer', description: 'Identifiant utilisateur.'),
                        new OA\Property(property: 'FRONT_COVER', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'BACK_COVER', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(property: 'SPINE', type: 'string', format: 'binary', nullable: true)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Résultat de l’analyse renvoyé par le service IA.',
                content: new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(type: 'string')
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Entrée invalide ou image manquante.')
        ]
    )]
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
}
