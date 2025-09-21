<?php

namespace App\DTO\Scan;

use App\DTO\Builder\AbstractDTOFactory;
use App\DTO\Scan\ScanWriteDTO;
use App\DTO\Scan\ScanReadDTO;
use App\Enum\BookScanPart;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\FileBag;

/**
 * @extends AbstractDTOFactory<ScanReadDTO, ScanReadDTO, ScanWriteDTO>
 */
class ScanDTOFactory extends AbstractDTOFactory
{

    public function readDtoFromEntity(object $entity): object
    {
        $this->logger->alert('readDtoFromEntity for ScanReadDTO is not implemented');
        return $entity;
    }

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        if (is_null($files)) {
            throw new InvalidArgumentException('File is missing');
        }
        $dto = new ScanWriteDTO(
            $files->get('FRONT_COVER'), //@phpstan-ignore-line
            $files->get('BACK_COVER'), //@phpstan-ignore-line
            $files->get('SPINE'), //@phpstan-ignore-line
            $inputBag->getInt('user'),
            $inputBag->getInt('id')
        );
        return $dto;
    }
}
