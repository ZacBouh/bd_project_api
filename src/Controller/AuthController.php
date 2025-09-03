<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AuthController extends AbstractController
{

    public function __construct(
        private LoggerInterface $logger,
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('/auth/register', name: 'auth_register', methods: 'POST')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $jsonData = $request->getContent();
        $this->logger->info("Received register request : $jsonData");
        $user = $serializer->deserialize($jsonData, User::class, 'json');
        if (is_null($user->getPassword())) {
            return $this->json(['message' => 'User registration request missing password'], Response::HTTP_BAD_REQUEST);
        }
        $hashedPassword = $passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashedPassword);
        $entityManager->persist($user);
        $entityManager->flush();
        return $this->json($user, 200, context: ['groups' => 'user:read']);
    }

    #[Route('/auth/login', name: 'auth_login', methods: 'POST')]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (is_null($user)) {
            return $this->json([
                'message' => 'missing credentials'
            ]);
        }
        return $this->json([
            'message' => 'successfully logged in',
            'user' => $user->getUserIdentifier(),
        ]);
    }

    #[Route('/auth/oauth2', name: 'auth_oauth_callback', methods: 'GET')]
    public function googleOauth2Callback(
        #[MapQueryParameter('code')] string $code,
        #[MapQueryParameter('state')] string $jsonEncodedState,
        #[MapQueryParameter('scope')] string $scope,
    ): RedirectResponse {
        $this->logger->critical("Received oauth2 request { code: $code, state: $jsonEncodedState, scope: '$scope'}");
        $configResponse = $this->httpClient->request('GET',  'https://accounts.google.com/.well-known/openid-configuration');
        /** @var array<string, string> $openIdConfig */
        $openIdConfig = $configResponse->toArray();
        $tokenEndpoint = $openIdConfig['token_endpoint'];
        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];
        $body = [
            'code' => $code,
            'client_id' => $_ENV['GOOGLE_OAUTH_CLIENT_ID'],
            'client_secret' => $_ENV['GOOGLE_OAUTH_CLIENT_SECRET'],
            'redirect_uri' => $_ENV['GOOGLE_OAUTH_REDIRECT_URI'],
            'grant_type' => 'authorization_code'
        ];
        // $this->logger->warning("Body generated : " . json_encode($body));
        // $this->logger->warning("Token endpoint  : " . $tokenEndpoint);
        $tokenResponse = $this->httpClient->request('POST', $tokenEndpoint, ['headers' => $headers, 'body' => $body]);
        // $this->logger->critical("Received token response : " . json_encode($tokenResponse->toArray()));
        /** @var string $access_token */
        $access_token = $tokenResponse->toArray()['access_token'];
        $this->logger->warning("Google OAuth access_token : $access_token");
        $userInfoResponse = $this->httpClient->request('GET', $openIdConfig["userinfo_endpoint"], ['headers' => ['Authorization' => "Bearer $access_token"]]);
        $this->logger->warning("retrieved user info from google : " . json_encode($userInfoResponse->toArray()));
        return $this->redirect('http://localhost:8082/titles');
    }
}
