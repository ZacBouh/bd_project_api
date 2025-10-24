<?php

namespace App\Controller;

use App\DTO\UploadedImage\UploadedImageDTOFactory;
use App\Service\UploadedImageService;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UploadedImageController extends AbstractController
{
    public function __construct(
        private UploadedImageService $uploadedImageService,
        private UploadedImageDTOFactory $uploadedImageDTOFactory,
    ) {}

    #[Route('/api/uploaded-images', name: 'uploaded_image_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $images = $this->uploadedImageService->getAllImages();
        $dtos = [];

        foreach ($images as $image) {
            $dtos[] = $this->uploadedImageDTOFactory->readDtoFromEntity($image);
        }

        return $this->json($dtos, Response::HTTP_OK);
    }

    #[Route('/api/uploaded-images/{id}', name: 'uploaded_image_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->uploadedImageService->deleteUploadedImage($id);
        } catch (EntityNotFoundException $exception) {
            return $this->json([
                'message' => $exception->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
