<?php

namespace App\Controller;

use App\DTO\Copy\CopyDTOFactory;
use App\Service\CopyManagerService;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CopyController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private CopyManagerService $copyService,
        private CopyDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/copy', name: 'copy_create', methods: 'POST')]
    public function createCopy(
        Request $request,
    ): JsonResponse {
        $this->logger->warning('Received Create Copy Request');

        $createdCopy = $this->copyService->createCopy($request->request, $request->files);
        $dto = $this->dtoFactory->readDTOFromEntity($createdCopy);
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
        Request $request
    ): JsonResponse {
        try {
            /** @var int|null $copyId */
            $copyId = json_decode($request->getContent(), true)['id'] ?? null; //@phpstan-ignore-line
            if (is_null($copyId)) {
                throw new InvalidArgumentException('The id is null ');
            }
            $this->logger->warning("Intenting to remove copy with id : $copyId");
            $this->copyService->removeCopy($copyId);
            return $this->json(['message' => 'Copy successfully removed, id : ' . $copyId]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'error' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR]);
        }
    }

    #[Route('/api/copy/update', name: 'copy_update', methods: 'POST')]
    public function updateCopy(
        Request $request
    ): JsonResponse {
        $updatedCopy = $this->copyService->updateCopy($request->request, $request->files);
        $dto = $this->dtoFactory->readDtoFromEntity($updatedCopy);
        return $this->json($dto);
    }

    #[Route('/api/copy/search', name: 'copy_search', methods: 'GET')]
    public function searchCopy(
        Request $request
    ): JsonResponse {
        try {

            $user = $this->getUser();
            $query = $request->query->getString('query');
            $limit = $request->query->getInt('limit', 200);
            $offset = $request->query->getInt('offset', 0);
            $forSale = $request->query->get('forSale', null) ? $request->query->getBoolean('forSale') : null;
            $this->logger->debug("Copy search request : $query limit: $limit offset: $offset, forSale: $forSale");
            $copies = $this->copyService->searchCopy($query, $limit, $offset, $forSale);
            $this->logger->debug(sprintf("Found %s copies for query $query", count($copies)));
            $dtos = [];
            foreach ($copies as $copy) {
                $dtos[] = $this->dtoFactory->readDtoFromEntity($copy);
            }
            return $this->json($dtos);
        } catch (InvalidArgumentException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (BadRequestException $e) {
            return $this->json($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }
}
