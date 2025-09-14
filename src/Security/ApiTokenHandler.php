<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

final class ApiTokenHandler implements AccessTokenHandlerInterface
{
    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        if ($accessToken !== $_ENV['AI_SERVICE_API_KEY']) {
            throw new BadCredentialsException("Invalid API key");
        } else {
            return new UserBadge('ai-service', function (string $identifier) {
                $user = new InMemoryUser($identifier, '', ['ROLE_ADMIN']);
                return $user;
            });
        }
    }
}
