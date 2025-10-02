<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AuthService
{
    public function __construct(
        private LoggerInterface $logger,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
        private HttpClientInterface $httpClient,
        private UserRepository $userRepo
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function registerUser(User $user): User
    {
        if (is_null($user->getPassword()) && is_null($user->getGoogleSub())) {
            $this->logger->error('Trying to register a user with no password and no google sub');
            throw new InvalidArgumentException('User need either a password or a googleSub to register');
        }
        if (!is_null($user->getPassword())) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
            $user->setPassword($hashedPassword);
        }
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function handleGoogleOauth2(string $code): User
    {
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
        $tokenResponse = $this->httpClient->request('POST', $tokenEndpoint, ['headers' => $headers, 'body' => $body]);

        /** @var string $access_token */
        $access_token = $tokenResponse->toArray()['access_token'];
        $this->logger->warning("Google OAuth access_token : $access_token");
        $userInfoResponse = $this->httpClient->request('GET', $openIdConfig["userinfo_endpoint"], ['headers' => ['Authorization' => "Bearer $access_token"]]);
        $userInfo = $userInfoResponse->toArray();

        if (!is_string($userInfo['sub']) || !is_string($userInfo['email']) || !is_bool($userInfo['email_verified'])) {
            $this->logger->error('Retrieved Google user info are of invalid type');
            throw new RuntimeException(sprintf("Retrieved Google user info are of invalid type %s", json_encode($userInfo)));
        }
        $this->logger->warning("retrieved user info from google : " . json_encode($userInfo));

        $qb = $this->userRepo->createQueryBuilder('u');
        $qb->where('u.email = :email')
            ->orWhere('u.googleSub = :googleSub')
            ->setParameter('email', $userInfo['email'])
            ->setParameter('googleSub', $userInfo['sub']);
        /** @var User|null $user */
        $user = $qb->getQuery()->getOneOrNullResult();
        if (is_null($user)) {
            $user = new User();
            $user->setEmail($userInfo['email'])
                ->setPseudo(explode("@", $userInfo['email'])[0])
                ->setGoogleSub($userInfo['sub'])
                ->setEmailVerified($userInfo['email_verified']);
            $this->registerUser($user);
            return $user;
        }
        if (is_null($user->getGoogleSub()) || !$user->getEmailVerified()) {
            $user->setGoogleSub($userInfo['sub']);
            $user->setEmailVerified($userInfo['email_verified']);
            $this->em->persist($user);
            $this->em->flush();
            return $user;
        }
        return $user;
    }
}
