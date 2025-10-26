<?php

namespace App\Controller\Traits;

use Symfony\Component\HttpFoundation\Request;
use function array_key_exists;

trait HardDeleteRequestTrait
{
    /**
     * @param array<string, mixed> $payload
     */
    private function shouldHardDelete(Request $request, array $payload = []): bool
    {
        $hardDelete = $request->query->getBoolean('hardDelete', false);

        if (array_key_exists('hardDelete', $payload)) {
            $hardDelete = $this->normalizeBoolean($payload['hardDelete']);
        }

        return $hardDelete;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return false;
    }
}
