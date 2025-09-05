<?php

namespace App\Entity\Trait;

use InvalidArgumentException;

/**
 * @phpstan-type NormalizeCallbackDefaultReturn array{'id': int, 'name': string} | array{'id': int} | array{'id': null, 'name': non-empty-string}
 * @template T of object
 * @mixin T
 */
trait HasDefaultNormalizeCallback
{
    /**
     * 
     * @return array{'id': int, 'name': string} | array{'id': int} | array{'id': null, 'name': non-empty-string}
     */
    public static function normalizeCallback(mixed $entity): array
    {
        if (!is_object($entity)) {
            throw new InvalidArgumentException('The provided property was not even an object');
        }
        if (!($entity instanceof self)) {
            return ['id' => null, 'name' => sprintf('The provided entity was not a %s instance', self::class)];
        }
        // @phpstan-ignore-next-line
        if (is_null($entity->getId()) && (!method_exists($entity, 'getName') || is_null($entity->getName()) || $entity->getName() === '')) {
            throw new InvalidArgumentException(sprintf('Cannot Normalize provided object of class %s it has no Id and no name', $entity::class));
            // @phpstan-ignore-next-line
        } else if (method_exists($entity, 'getName')) {
            $name = $entity->getName();
            // @phpstan-ignore-next-line
            if (is_string($name) && mb_strlen($name) > 0) {
                return ['id' => $entity->getId(), 'name' => $name];
            }
        }
        return ['id' => $entity->getId()];
    }
}
