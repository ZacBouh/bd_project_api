<?php

namespace App\Mapper;

use App\DTO\User\UserWriteDTO;
use App\Entity\User;

/**
 * @extends AbstractEntityMapper<User, UserWriteDTO>
 */
class UserEntityMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return User::class;
    }

    protected function instantiateEntity(): object
    {
        return new User();
    }

    protected function getNormalizerIgnoredFields(): array
    {
        return ['id'];
    }
}
