<?php

namespace App\DTO\Copy;

use App\DTO\Builder\AbstractDTOBuilder;
use App\DTO\Builder\DTOBuilder;
use App\DTO\Builder\EntityDTOBuilderInterface;
use App\Entity\Copy;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @extends AbstractDTOBuilder<Copy>
 */
class CopyDTOBuilder extends AbstractDTOBuilder
{

    public function buildReadDTO(): CopyReadDTO
    {
        return parent::denormalizeToDTO(CopyReadDTO::class);
    }


    public function buildWriteDTO(): CopyWriteDTO
    {
        return parent::denormalizeToDTO(CopyWriteDTO::class);
    }
}
