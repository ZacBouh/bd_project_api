<?php

namespace App\Mapper;

use App\Entity\Artist;
use App\DTO\Artist\ArtistWriteDTO;

/**
 * @extends AbstractEntityMapper<Artist, ArtistWriteDTO>
 */
class ArtistEntityMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Artist::class;
    }

    protected function instantiateEntity(): object
    {
        return new Artist();
    }
}
