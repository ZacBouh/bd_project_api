<?php

namespace App\Mapper;

use App\DTO\Series\SeriesWriteDTO;
use App\Entity\Publisher;
use App\Entity\Series;
use App\Entity\Title;
use App\Entity\UploadedImage;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @extends AbstractEntityMapper<Series, SeriesWriteDTO>
 */
class SeriesMapper extends AbstractEntityMapper
{
    protected function getEntityClass(): string
    {
        return Series::class;
    }

    protected function instantiateEntity(): object
    {
        return new Series();
    }
}
