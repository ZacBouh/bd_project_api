<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Order\OrderDTOFactory;
use App\DTO\Order\OrderReadDTO;
use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Service\OrderService;
use LogicException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/orders')]
class OrderController extends AbstractController
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderService $orderService,
        private OrderDTOFactory $orderDTOFactory,
        private UserRepository $userRepository,
    ) {
    }

    #[Route('', name: 'orders_list', methods: ['GET'])]
    #[OA\Get(
        summary: 'Lister les commandes',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'userId', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste des commandes',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: OrderReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Paramètres invalides.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Utilisateur introuvable.'),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $requestedBuyer = null;
        $userIdRaw = $request->query->get('userId');
        if ($userIdRaw !== null) {
            if (filter_var($userIdRaw, FILTER_VALIDATE_INT) === false) {
                return new JsonResponse(['error' => 'userId must be an integer'], Response::HTTP_BAD_REQUEST);
            }

            $requestedBuyer = $this->userRepository->find((int) $userIdRaw);
            if ($requestedBuyer === null) {
                return new JsonResponse(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            if (!$this->isGranted('ROLE_ADMIN') && $requestedBuyer->getId() !== $user->getId()) {
                return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
            }
        }

        $buyerFilter = $requestedBuyer;
        if ($buyerFilter === null && !$this->isGranted('ROLE_ADMIN')) {
            $buyerFilter = $user;
        }

        $orders = $this->orderRepository->findForListing($buyerFilter);

        $dtos = array_map(function (Order $order): OrderReadDTO {
            /** @var OrderReadDTO $dto */
            $dto = $this->orderDTOFactory->readDtoFromEntity($order);

            return $dto;
        }, $orders);

        return $this->json($dtos, Response::HTTP_OK);
    }

    #[Route('/{orderRef}', name: 'orders_show', methods: ['GET'])]
    #[OA\Get(
        summary: 'Afficher une commande',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'orderRef', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Commande trouvée.',
                content: new OA\JsonContent(ref: new Model(type: OrderReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Commande introuvable.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
        ]
    )]
    public function show(string $orderRef): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $order = $this->orderRepository->findOneForViewer(
            $orderRef,
            $this->isGranted('ROLE_ADMIN') ? null : $user
        );
        if ($order === null) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        /** @var OrderReadDTO $dto */
        $dto = $this->orderDTOFactory->readDtoFromEntity($order);

        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/confirm', name: 'orders_confirm_item', methods: ['POST'])]
    #[OA\Post(
        summary: 'Confirmer la remise d’un exemplaire',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'orderRef', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'itemId', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'État mis à jour.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Commande invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Élément de commande introuvable.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
        ]
    )]
    public function confirmItem(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $orderRef = $request->query->get('orderRef');
        $itemIdRaw = $request->query->get('itemId');

        if (!is_string($orderRef) || $orderRef === '') {
            return new JsonResponse(['error' => 'orderRef is required'], Response::HTTP_BAD_REQUEST);
        }

        if ($itemIdRaw === null || filter_var($itemIdRaw, FILTER_VALIDATE_INT) === false) {
            return new JsonResponse(['error' => 'itemId must be an integer'], Response::HTTP_BAD_REQUEST);
        }

        $itemId = (int) $itemIdRaw;

        $order = $this->orderRepository->findOneForBuyer($orderRef, $user);
        if ($order === null) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $orderItem = $this->orderService->findOrderItem($order, $itemId);
        if ($orderItem === null) {
            return new JsonResponse(['error' => 'Order item not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->orderService->confirmOrderItem($orderItem, $user);
        } catch (LogicException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        /** @var OrderReadDTO $dto */
        $dto = $this->orderDTOFactory->readDtoFromEntity($order);

        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/cancel', name: 'orders_cancel_item', methods: ['POST'])]
    #[OA\Post(
        summary: 'Annuler l\'achat d’un exemplaire',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'orderRef', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'itemId', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Commande mise à jour.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Commande invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Élément de commande introuvable.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
        ]
    )]
    public function cancelItem(Request $request): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $orderRef = $request->query->get('orderRef');
        $itemIdRaw = $request->query->get('itemId');

        if (!is_string($orderRef) || $orderRef === '') {
            return new JsonResponse(['error' => 'orderRef is required'], Response::HTTP_BAD_REQUEST);
        }

        if ($itemIdRaw === null || filter_var($itemIdRaw, FILTER_VALIDATE_INT) === false) {
            return new JsonResponse(['error' => 'itemId must be an integer'], Response::HTTP_BAD_REQUEST);
        }

        $itemId = (int) $itemIdRaw;

        $order = $this->orderRepository->findOneForBuyer($orderRef, $user);
        if ($order === null) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        $orderItem = $this->orderService->findOrderItem($order, $itemId);
        if ($orderItem === null) {
            return new JsonResponse(['error' => 'Order item not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->orderService->cancelOrderItem($orderItem, $user);
        } catch (LogicException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        /** @var OrderReadDTO $dto */
        $dto = $this->orderDTOFactory->readDtoFromEntity($order);

        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/{orderRef}/cancel', name: 'orders_cancel', methods: ['POST'])]
    #[OA\Post(
        summary: 'Annuler une commande',
        tags: ['Orders'],
        parameters: [
            new OA\Parameter(name: 'orderRef', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Commande annulée.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Commande invalide.'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Commande introuvable.'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'Utilisateur non authentifié.'),
        ]
    )]
    public function cancelOrder(string $orderRef): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        if ($user === null) {
            return new JsonResponse(['error' => 'Authentication required'], Response::HTTP_UNAUTHORIZED);
        }

        $order = $this->orderRepository->findOneForBuyer($orderRef, $user);
        if ($order === null) {
            return new JsonResponse(['error' => 'Order not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $this->orderService->cancelOrder($order, $user);
        } catch (LogicException $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        }

        /** @var OrderReadDTO $dto */
        $dto = $this->orderDTOFactory->readDtoFromEntity($order);

        return $this->json($dto, Response::HTTP_OK);
    }

    private function getAuthenticatedUser(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }
}
