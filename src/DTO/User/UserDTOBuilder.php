<?php

namespace App\DTO\User;

use App\DTO\Builder\AbstractDTOBuilder;
use App\Entity\User;

/**
 * @extends AbstractDTOBuilder<User>
 */
class UserDTO extends AbstractDTOBuilder
{
    public function buildReadDTO(): UserReadDTO
    {
        return parent::denormalizeToDTO(UserReadDTO::class);
    }
}
