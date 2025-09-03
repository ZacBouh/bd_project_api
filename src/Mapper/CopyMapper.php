<?php

namespace App\Mapper;

use App\DTO\Copy\CopyWriteDTO;
use App\Entity\Copy;
use App\Entity\UploadedImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use App\Entity\Title;
use App\Entity\User;

/**
 * @extends AbstractEntityMapper<Copy, CopyWriteDTO>
 */
class CopyMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Copy::class;
    }

    protected function instantiateEntity(): object
    {
        return new Copy();
    }
}
