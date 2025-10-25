<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;

final class AuthController extends AbstractController
{

    public function __construct(
        private LoggerInterface $logger,
        private AuthService $authService,
        private JWTTokenManagerInterface $jwtManager
    ) {}

    #[Route('/', name: 'healt_check', methods: 'GET')]
    #[OA\Get(
        summary: 'Vérifier la disponibilité de l’API',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'L’API est disponible.',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string', example: 'Server Up :)')
                )
            )
        ]
    )]
    public function healtCheck(): Response
    {
        return new Response("Server Up :)");
    }

    #[Route('/auth/register', name: 'auth_register', methods: 'POST')]
    #[OA\Post(
        summary: 'Créer un compte utilisateur',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password', 'pseudo'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'pseudo', type: 'string'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Utilisateur créé avec succès.',
                content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read']))
            ),
            new OA\Response(
                response: Response::HTTP_CONFLICT,
                description: 'Un compte avec cette adresse e-mail existe déjà.'
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Erreur inattendue lors de l’inscription.'
            )
        ]
    )]
    public function register(Request $request, SerializerInterface $serializer): JsonResponse
    {
        try {
            $jsonData = $request->getContent();
            $this->logger->info("Received register request : $jsonData");
            $user = $serializer->deserialize($jsonData, User::class, 'json');
            $this->authService->registerUser($user);
            return $this->json($user, 200, context: ['groups' => 'user:read']);
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $error) {
            return $this->json(['message' => 'An account with this email already exists'], Response::HTTP_CONFLICT);
        } catch (\Throwable $error) {
            return $this->json($error, Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/auth/login', name: 'auth_login', methods: 'POST')]
    #[OA\Post(
        summary: 'Authentification utilisateur (JWT)',
        description: 'Le traitement de l’authentification est réalisé par LexikJWTAuthenticationBundle. Cette route ne doit pas être invoquée directement.',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: Response::HTTP_BAD_GATEWAY,
                description: 'La logique de connexion est gérée par un listener Lexik.'
            )
        ]
    )]
    public function login(#[CurrentUser] ?User $user): Response
    {
        $this->logger->critical("User login managed by lexik_jwt_authentication and AuthenticationSuccessListener");
        return new Response("You have nothing to do here", Response::HTTP_BAD_GATEWAY);
    }

    #[Route('/api/user', name: 'api_get_user', methods: 'GET')]
    #[OA\Get(
        summary: 'Récupérer le profil de l’utilisateur courant',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Informations du compte connecté.',
                content: new OA\JsonContent(ref: new Model(type: User::class, groups: ['user:read']))
            )
        ]
    )]
    public function getUserInfo(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json($user, 200, context: ['groups' => 'user:read']);
    }

    #[Route('/auth/oauth2', name: 'auth_oauth_callback', methods: 'GET')]
    #[OA\Get(
        summary: 'Callback OAuth2 Google',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(name: 'code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'state', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'scope', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_FOUND,
                description: 'Redirection vers le frontend avec le token JWT.'
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: 'Échec de l’authentification OAuth2.'
            )
        ]
    )]
    public function googleOauth2Callback(
        #[MapQueryParameter('code')] string $code,
        #[MapQueryParameter('state')] string $jsonEncodedState,
        #[MapQueryParameter('scope')] string $scope,
    ): RedirectResponse {
        $this->logger->critical("Received oauth2 request { code: $code, state: $jsonEncodedState, scope: '$scope'}");
        $user = $this->authService->handleGoogleOauth2($code);
        $this->logger->debug('Successfully logged in user ' . $user->getPseudo() . " email: " . $user->getEmail());
        $token = $this->jwtManager->create($user);
        return $this->redirect(
            'http://localhost:8082/oauth#token=' . urlencode($token)
        );
    }

    #[Route('/auth/verify-email', name: 'verify_email', methods: 'GET')]
    #[OA\Get(
        summary: 'Valider une adresse e-mail',
        tags: ['Authentication'],
        parameters: [
            new OA\Parameter(
                name: 'token',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string'),
                description: 'Jeton de validation transmis par e-mail.'
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_BAD_REQUEST,
                description: 'Le jeton est manquant.'
            ),
            new OA\Response(
                response: Response::HTTP_FOUND,
                description: 'Redirection vers la page de connexion après validation.'
            )
        ]
    )]
    public function verifyEmail(Request $request): Response
    {
        $token = $request->query->getString('token');
        if ($token === '') {
            return new Response("Missing Token", Response::HTTP_BAD_REQUEST);
        }
        $user = $this->authService->handleEmailValidation($token);
        return new RedirectResponse('http://localhost:8082/login');
    }
}
