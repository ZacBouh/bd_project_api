<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;

class AuthenticationSuccessListener
{
    public function __construct(
        private Serializer $serializer,
        private LoggerInterface $logger
    ) {}

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $this->logger->info('AuthenticationSuccessListener is called');
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) { // @phpstan-ignore-line
            return;
        }

        $userData = $this->serializer->normalize(
            $user,
            null,
            [AbstractNormalizer::GROUPS => ['user:read']]
        );

        $data['user'] = $userData;
        $event->setData($data);
    }
}
