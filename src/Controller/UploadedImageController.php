<?php

namespace App\Controller;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Service\UploadedImageService;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class UploadedImageController extends AbstractController
{
    public function __construct(
        private UploadedImageService $imageService,
    ) {}

    #[Route('/api/uploaded-images', name: 'uploaded_images_list', methods: 'GET')]
    #[OA\Get(
        summary: 'Récupérer toutes les images téléversées',
        tags: ['Uploaded Images'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des images téléversées.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: UploadedImageReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.')
        ]
    )]
    public function list(): JsonResponse
    {
        try {
            $dtos = $this->imageService->getAllImages();
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        }

        return $this->json($dtos);
    }

    #[Route('/api/uploaded-images/{id}', name: 'uploaded_images_update', requirements: ['id' => '\\d+'], methods: 'PATCH')]
    #[OA\Patch(
        summary: 'Mettre à jour une image téléversée',
        tags: ['Uploaded Images'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: 'imageName', type: 'string', nullable: true),
                        new OA\Property(property: 'imageFile', type: 'string', format: 'binary', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Image mise à jour.',
                content: new OA\JsonContent(ref: new Model(type: UploadedImageReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Image introuvable.')
        ]
    )]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $dto = $this->imageService->updateImage($id, $request->request, $request->files);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json($dto);
    }

    #[Route('/api/uploaded-images', name: 'uploaded_images_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer des images téléversées',
        tags: ['Uploaded Images'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['ids'],
                properties: [
                    new OA\Property(
                        property: 'ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer'),
                        description: 'Identifiants des images à supprimer.'
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Images supprimées.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Une des images est introuvable.')
        ]
    )]
    public function delete(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            $payload = [];
        }

        $ids = $payload['ids'] ?? [];
        if (!is_array($ids)) {
            return $this->json(['message' => 'ids must be an array of integers.'], Response::HTTP_BAD_REQUEST);
        }

        $imageIds = [];
        foreach ($ids as $id) {
            if (!is_numeric($id)) {
                return $this->json(['message' => 'Each id must be numeric.'], Response::HTTP_BAD_REQUEST);
            }
            $intId = (int) $id;
            if ($intId <= 0) {
                return $this->json(['message' => 'Each id must be a positive integer.'], Response::HTTP_BAD_REQUEST);
            }
            $imageIds[] = $intId;
        }

        try {
            $this->imageService->removeImages($imageIds);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'message' => 'Uploaded images removed successfully.',
        ]);
    }
}
