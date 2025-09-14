<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\AccessToken\AccessTokenExtractorInterface;

final class ApiTokenExtractor implements AccessTokenExtractorInterface
{
    public function extractAccessToken(Request $request): ?string
    {
        $token = $request->headers->get('X-API-Key');
        return $token ?: null;
    }
}
