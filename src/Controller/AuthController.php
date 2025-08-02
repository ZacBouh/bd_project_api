<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Serializer\SerializerInterface;


final class AuthController extends AbstractController
{

    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('/auth/register', name: 'auth_register', methods: 'POST')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer, EntityManagerInterface $entityManager): JsonResponse
    {
        $jsonData = $request->getContent();
        $this->logger->info("Received register request : $jsonData");
        $user = $serializer->deserialize($jsonData, User::class, 'json');
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
}
