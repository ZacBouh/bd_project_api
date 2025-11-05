<?php

namespace App\Controller;

use App\Controller\Traits\HardDeleteRequestTrait;
use App\DTO\Copy\CopyDTOFactory;
use App\DTO\Copy\CopyReadDTO;
use App\Enum\CopyCondition;
use App\Enum\Language;
use App\Service\CopyManagerService;
use InvalidArgumentException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CopyController extends AbstractController
{
    use HardDeleteRequestTrait;

    public function __construct(
        private LoggerInterface $logger,
        private CopyManagerService $copyService,
        private CopyDTOFactory $dtoFactory,
    ) {}

    #[Route('/api/copy', name: 'copy_create', methods: 'POST')]
    #[OA\Post(
        summary: 'Créer un exemplaire',
        tags: ['Copies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['ownerId', 'titleId', 'copyCondition'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', nullable: true),
                        new OA\Property(property: 'ownerId', type: 'integer'),
                        new OA\Property(property: 'titleId', type: 'integer'),
                        new OA\Property(property: 'copyCondition', type: 'string', description: 'Valeur de l’énumération CopyCondition.'),
                        new OA\Property(property: 'price', type: 'integer', nullable: true, description: 'Prix en centimes.'),
                        new OA\Property(property: 'currency', type: 'string', nullable: true, description: 'Code devise PriceCurrency.'),
                        new OA\Property(property: 'boughtForPrice', type: 'integer', nullable: true, description: 'Prix d\'achat en centimes.'),
                        new OA\Property(property: 'boughtForCurrency', type: 'string', nullable: true, description: 'Code devise PriceCurrency.'),
                        new OA\Property(property: 'forSale', type: 'boolean', nullable: true),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(
                            property: 'uploadedImages',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Exemplaire créé.',
                content: new OA\JsonContent(ref: new Model(type: CopyReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function createCopy(
        Request $request,
    ): JsonResponse {
        $this->logger->warning('Received Create Copy Request');

        $createdCopy = $this->copyService->createCopy($request->request, $request->files);
        $dto = $this->dtoFactory->readDTOFromEntity($createdCopy);
        return $this->json($dto, Response::HTTP_OK);
    }

    #[Route('/api/copy', name: 'copy_get', methods: 'GET')]
    #[OA\Get(
        summary: 'Lister les exemplaires',
        tags: ['Copies'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste de tous les exemplaires.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: CopyReadDTO::class))
                )
            )
        ]
    )]
    public function getCopies(
        Request $request,
    ): JsonResponse {
        $this->logger->critical('Received Get Copies Request');

        $data = $this->copyService->getCopies();

        return $this->json($data);
    }

    #[Route('/api/copy/for-sale', name: 'copy_for_sale_list', methods: 'GET')]
    #[OA\Get(
        summary: 'Lister les exemplaires en vente avec filtres',
        tags: ['Copies'],
        parameters: [
            new OA\Parameter(
                name: 'limit',
                in: 'query',
                description: 'Nombre maximum de résultats à retourner',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1, default: 20)
            ),
            new OA\Parameter(
                name: 'offset',
                in: 'query',
                description: 'Décalage de pagination',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 0, default: 0)
            ),
            new OA\Parameter(
                name: 'copyCondition',
                in: 'query',
                description: 'Filtrer par état minimal de l\'exemplaire (mint, near_mint, very_fine, fine, very_good, good, fair, poor). Inclut automatiquement les états meilleurs.',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['mint', 'near_mint', 'very_fine', 'fine', 'very_good', 'good', 'fair', 'poor']
                )
            ),
            new OA\Parameter(
                name: 'minPrice',
                in: 'query',
                description: 'Prix minimum en centimes',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 0)
            ),
            new OA\Parameter(
                name: 'maxPrice',
                in: 'query',
                description: 'Prix maximum en centimes',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 0)
            ),
            new OA\Parameter(
                name: 'titleLanguage',
                in: 'query',
                description: 'Langue du titre (code ISO 639-1)',
                required: false,
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['ar', 'de', 'en', 'es', 'fr', 'hi', 'it', 'ja', 'ko', 'nl', 'pl', 'pt', 'ru', 'sv', 'tr', 'uk', 'zh']
                )
            ),
            new OA\Parameter(
                name: 'titlePublisher',
                in: 'query',
                description: 'Identifiant de l\'éditeur du titre',
                required: false,
                schema: new OA\Schema(type: 'integer', minimum: 1)
            ),
            new OA\Parameter(
                name: 'titleIsbn',
                in: 'query',
                description: 'ISBN exact du titre',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'order',
                in: 'query',
                description: 'Ordre de tri (ASC ou DESC)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC'], default: 'DESC')
            ),
            new OA\Parameter(
                name: 'orderBy',
                in: 'query',
                description: 'Champ de tri (updatedAt ou price)',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['updatedAt', 'price'], default: 'updatedAt')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Liste paginée des exemplaires en vente.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: CopyReadDTO::class))
                )
            ),
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Requête invalide'
            )
        ]
    )]
    public function getCopiesForSale(Request $request): JsonResponse
    {
        $limit = $request->query->getInt('limit', 20);
        if ($limit < 1) {
            throw new BadRequestException('limit must be greater than 0');
        }

        $offset = $request->query->getInt('offset', 0);
        if ($offset < 0) {
            throw new BadRequestException('offset cannot be negative');
        }

        $orderValue = $request->query->get('order');
        $order = 'DESC';
        if ($orderValue !== null && $orderValue !== '') {
            $order = strtoupper((string) $orderValue);
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            throw new BadRequestException('order must be either ASC or DESC');
        }

        $copyCondition = null;
        $copyConditionValue = $request->query->get('copyCondition');
        if ($copyConditionValue !== null && $copyConditionValue !== '') {
            $copyCondition = CopyCondition::tryFrom($copyConditionValue);
            if (is_null($copyCondition)) {
                throw new BadRequestException('Invalid copyCondition value');
            }
        }

        $orderFieldRaw = $request->query->get('orderBy');
        $orderFieldNormalized = 'updatedat';
        if ($orderFieldRaw !== null && $orderFieldRaw !== '') {
            $orderFieldNormalized = strtolower((string) $orderFieldRaw);
        }
        $allowedOrderFields = [
            'updatedat' => 'updatedAt',
            'price' => 'price',
        ];
        if (!array_key_exists($orderFieldNormalized, $allowedOrderFields)) {
            throw new BadRequestException('orderBy must be either updatedAt or price');
        }
        $orderField = $allowedOrderFields[$orderFieldNormalized];

        $language = null;
        $languageValue = $request->query->get('titleLanguage');
        if ($languageValue !== null && $languageValue !== '') {
            $language = Language::tryFrom($languageValue);
            if (is_null($language)) {
                throw new BadRequestException('Invalid titleLanguage value');
            }
        }

        $minPrice = null;
        if ($request->query->has('minPrice')) {
            $minPriceRaw = $request->query->get('minPrice');
            if ($minPriceRaw === '' || $minPriceRaw === null) {
                throw new BadRequestException('minPrice cannot be empty');
            }
            $minPriceSanitized = trim((string) $minPriceRaw);
            if ($minPriceSanitized === '' || !ctype_digit($minPriceSanitized)) {
                throw new BadRequestException('minPrice must be a non-negative integer');
            }
            $minPrice = (int) $minPriceSanitized;
            if ($minPrice < 0) {
                throw new BadRequestException('minPrice cannot be negative');
            }
        }

        $maxPrice = null;
        if ($request->query->has('maxPrice')) {
            $maxPriceRaw = $request->query->get('maxPrice');
            if ($maxPriceRaw === '' || $maxPriceRaw === null) {
                throw new BadRequestException('maxPrice cannot be empty');
            }
            $maxPriceSanitized = trim((string) $maxPriceRaw);
            if ($maxPriceSanitized === '' || !ctype_digit($maxPriceSanitized)) {
                throw new BadRequestException('maxPrice must be a non-negative integer');
            }
            $maxPrice = (int) $maxPriceSanitized;
            if ($maxPrice < 0) {
                throw new BadRequestException('maxPrice cannot be negative');
            }
        }

        if (!is_null($minPrice) && !is_null($maxPrice) && $minPrice > $maxPrice) {
            throw new BadRequestException('minPrice cannot be greater than maxPrice');
        }

        $publisherId = null;
        $publisherValue = $request->query->get('titlePublisher');
        if ($publisherValue !== null && $publisherValue !== '') {
            $publisherSanitized = trim((string) $publisherValue);
            if ($publisherSanitized === '' || !ctype_digit($publisherSanitized)) {
                throw new BadRequestException('titlePublisher must be a positive integer');
            }
            $publisherId = (int) $publisherSanitized;
            if ($publisherId < 1) {
                throw new BadRequestException('titlePublisher must be a positive integer');
            }
        }

        $isbn = null;
        $isbnValue = $request->query->get('titleIsbn');
        if ($isbnValue !== null && $isbnValue !== '') {
            $isbn = trim((string) $isbnValue);
            if ($isbn === '') {
                throw new BadRequestException('titleIsbn cannot be empty');
            }
        }

        $copies = $this->copyService->getCopiesForSale(
            $limit,
            $offset,
            $copyCondition,
            $minPrice,
            $maxPrice,
            $language,
            $publisherId,
            $isbn,
            $order,
            $orderField,
        );

        return $this->json($copies);
    }

    #[Route('/api/copy', name: 'copy_remove', methods: 'DELETE')]
    #[OA\Delete(
        summary: 'Supprimer un exemplaire',
        tags: ['Copies'],
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
                    new OA\Property(property: 'id', type: 'integer', description: 'Identifiant de l’exemplaire à supprimer.'),
                    new OA\Property(property: 'hardDelete', type: 'boolean', nullable: true, description: 'Forcer la suppression définitive (administrateur uniquement).')
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Exemplaire supprimé.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string')
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Erreur lors de la suppression.')
        ]
    )]
    public function removeCopy(
        Request $request
    ): JsonResponse {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                $payload = [];
            }
            /** @var int|null $copyId */
            $copyId = $payload['id'] ?? null; //@phpstan-ignore-line
            if (is_null($copyId)) {
                throw new InvalidArgumentException('The id is null ');
            }
            $hardDelete = $this->shouldHardDelete($request, $payload);
            $this->logger->warning("Intenting to remove copy with id : $copyId");
            $this->copyService->removeCopy($copyId, $hardDelete);
            return $this->json(['message' => 'Copy successfully removed, id : ' . $copyId]);
        } catch (InvalidArgumentException $e) {
            return $this->json(['message' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\Exception $e) {
            return $this->json(['message' => 'error' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR]);
        }
    }

    #[Route('/api/copy/update', name: 'copy_update', methods: 'POST')]
    #[OA\Post(
        summary: 'Mettre à jour un exemplaire',
        tags: ['Copies'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['id'],
                    properties: [
                        new OA\Property(property: 'id', type: 'integer'),
                        new OA\Property(property: 'ownerId', type: 'integer', nullable: true),
                        new OA\Property(property: 'titleId', type: 'integer', nullable: true),
                        new OA\Property(property: 'copyCondition', type: 'string', nullable: true),
                        new OA\Property(property: 'price', type: 'integer', nullable: true, description: 'Prix en centimes.'),
                        new OA\Property(property: 'currency', type: 'string', nullable: true),
                        new OA\Property(property: 'boughtForPrice', type: 'integer', nullable: true, description: 'Prix d\'achat en centimes.'),
                        new OA\Property(property: 'boughtForCurrency', type: 'string', nullable: true),
                        new OA\Property(property: 'forSale', type: 'boolean', nullable: true),
                        new OA\Property(property: 'coverImageFile', type: 'string', format: 'binary', nullable: true),
                        new OA\Property(
                            property: 'uploadedImages',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            nullable: true
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Exemplaire mis à jour.',
                content: new OA\JsonContent(ref: new Model(type: CopyReadDTO::class))
            ),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Accès refusé.'),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Requête invalide.')
        ]
    )]
    public function updateCopy(
        Request $request
    ): JsonResponse {
        $updatedCopy = $this->copyService->updateCopy($request->request, $request->files);
        $dto = $this->dtoFactory->readDtoFromEntity($updatedCopy);
        return $this->json($dto);
    }

    #[Route('/api/copy/search', name: 'copy_search', methods: 'GET')]
    #[OA\Get(
        summary: 'Rechercher des exemplaires',
        tags: ['Copies'],
        parameters: [
            new OA\Parameter(name: 'query', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 200, minimum: 1)),
            new OA\Parameter(name: 'offset', in: 'query', schema: new OA\Schema(type: 'integer', default: 0, minimum: 0)),
            new OA\Parameter(name: 'forSale', in: 'query', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Résultats de la recherche.',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: new Model(type: CopyReadDTO::class))
                )
            ),
            new OA\Response(response: Response::HTTP_BAD_REQUEST, description: 'Paramètres de recherche invalides.')
        ]
    )]
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
