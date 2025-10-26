<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\User\UserDTOFactory;
use App\DTO\User\UserReadDTO;
use App\Service\UserManagerService;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class UserController extends AbstractController
{
    use HardDeleteRequestTrait;

    public function __construct(
        private LoggerInterface $logger,
        private UserManagerService $userManager,
        private UserDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/users', name: 'users_list', methods: 'GET')]
    #[OA\Get(
        summary: 'Récupérer la liste de tous les utilisateurs',
        tags: ['Users'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des utilisateurs.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: UserReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.')
        ]
    )]
    public function listUsers(): JsonResponse
    {
        try {
            $users = $this->userManager->getAllUsers();
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        }

        $dtos = array_map(fn($user) => $this->dtoFactory->readDtoFromEntity($user), $users);

        return $this->json($dtos);
    }

    #[Route('/api/users/update', name: 'users_update', methods: 'POST')]
    #[OA\Post(
        summary: 'Mettre à jour un utilisateur',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id', 'email'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'email', type: 'string', format: 'email'),
                        new OA\Property(property: 'pseudo', type: 'string', nullable: true),
                        new OA\Property(property: 'password', type: 'string', format: 'password', nullable: true),
                        new OA\Property(
                            property: 'roles',
                            type: 'array',
                            items: new OA\Items(type: 'string'),
                            nullable: true
                        ),
                        new OA\Property(property: 'googleSub', type: 'string', nullable: true),
                        new OA\Property(property: 'emailVerified', type: 'boolean', nullable: true),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Utilisateur mis à jour.',
                content: new OA\JsonContent(ref: new Model(type: UserReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Utilisateur introuvable.')
        ]
    )]
    public function updateUser(Request $request): JsonResponse
    {
        try {
            $updatedUser = $this->userManager->updateUser($request->request);
        } catch (ValidationFailedException $exception) {
            return $this->json([
                'message' => 'Validation failed',
                'errors' => (string) $exception,
            ], Response::HTTP_BAD_REQUEST);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $dto = $this->dtoFactory->readDtoFromEntity($updatedUser);
        return $this->json($dto);
    }

    #[Route('/api/users', name: 'users_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer un utilisateur',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'hardDelete',
                in: 'query',
                description: 'Forcer la suppression définitive (administrateur uniquement).',
                schema: new OA\Schema(type: 'boolean'),
                required: false
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['id'],
                properties: [
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant de l’utilisateur à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Utilisateur supprimé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Utilisateur introuvable.')
        ]
    )]
    public function removeUser(Request $request): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $userId */
            $userId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($userId)) {
                throw new InvalidArgumentException('The id is null');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning(sprintf('Attempting to remove user with id : %d', $userId));
            $this->userManager->removeUser((int) $userId, $hardDelete);
        } catch (InvalidArgumentException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (AccessDeniedException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_FORBIDDEN);
        } catch (ResourceNotFoundException $exception) {
            return $this->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['message' => 'User successfully removed, id : ' . $userId]);
    }
}
