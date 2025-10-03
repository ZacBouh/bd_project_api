<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
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
    public function healtCheck(): Response
    {
        return new Response("Server Up :)");
    }

    #[Route('/auth/register', name: 'auth_register', methods: 'POST')]
    public function register(Request $request, SerializerInterface $serializer): JsonResponse
    {
        $jsonData = $request->getContent();
        $this->logger->info("Received register request : $jsonData");
        $user = $serializer->deserialize($jsonData, User::class, 'json');
        $this->authService->registerUser($user);
        return $this->json($user, 200, context: ['groups' => 'user:read']);
    }

    #[Route('/auth/login', name: 'auth_login', methods: 'POST')]
    public function login(#[CurrentUser] ?User $user): Response
    {
        $this->logger->critical("User login managed by lexik_jwt_authentication and AuthenticationSuccessListener");
        return new Response("You have nothing to do here", Response::HTTP_BAD_GATEWAY);
    }

    #[Route('/api/user', name: 'api_get_user', methods: 'GET')]
    public function getUserInfo(): JsonResponse
    {
        $user = $this->getUser();
        return $this->json($user, 200, context: ['groups' => 'user:read']);
    }

    #[Route('/auth/oauth2', name: 'auth_oauth_callback', methods: 'GET')]
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
