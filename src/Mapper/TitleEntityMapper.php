<?php

namespace App\Mapper;

use App\Entity\Title;
use App\DTO\Title\TitleWriteDTO;

/**
 * @extends AbstractEntityMapper<Title, TitleWriteDTO>
 */
class TitleEntityMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Title::class;
    }

    protected function instantiateEntity(): object
    {
        return new Title();
    }
}
