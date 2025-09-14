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
        $bookPart = $inputBag->getEnum('value', BookScanPart::class);
        if (is_null($bookPart)) {
            throw new InvalidArgumentException('Book part information missing');
        }
        $imageFile = $files->get($bookPart->value);
        if (!($imageFile instanceof UploadedFile)) {
            throw new InvalidArgumentException('File is not an instance of UploadedFile');
        }

        $dto = new ScanWriteDTO(
            $bookPart,
            $inputBag->getString('label'),
            $imageFile,
            $inputBag->getInt('user'),
            $inputBag->getInt('id')
        );
        return $dto;
    }
}
