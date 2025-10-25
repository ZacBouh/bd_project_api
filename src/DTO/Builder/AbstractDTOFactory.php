<?php

namespace App\DTO\Builder;

use App\DTO\UploadedImage\UploadedImageDTOFactory;
use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\UploadedImage;
use InvalidArgumentException;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ServiceLocatorTrait;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\Attribute\Required;
use App\Enum\Language;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 *  @template TEntity of object
 *  @template ReadDTO of object
 *  @template WriteDTO of object
 */
abstract class AbstractDTOFactory implements ServiceSubscriberInterface
{
    use ServiceMethodsSubscriberTrait;
    protected LoggerInterface $logger;
    protected UploadedImageDTOFactory $imageDTOFactory;

    public function __construct() {}

    /**
     * @param TEntity $entity
     * @return ReadDTO
     */
    abstract function readDtoFromEntity(object $entity): object;

    /**
     * @param InputBag<scalar> $inputBag
     * @param FileBag $files
     * @return WriteDTO 
     */
    abstract function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object;

    #[Required]
    public function setDependencies(
        LoggerInterface $logger,
        UploadedImageDTOFactory $imageDTOFactory
    ): void {
        $this->logger = $logger;
        $this->imageDTOFactory = $imageDTOFactory;
    }

    protected function validateId(?object $entity = null): int
    {
        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf("Entity provided is null"));
        }
        if (!method_exists($entity, 'getId')) {
            throw new InvalidArgumentException(sprintf("%s doest not expose getId method", $entity::class));
        }
        if (is_null($entity->getId())) {
            throw new InvalidArgumentException($entity::class . " object is not persisted, its id is null");
        }
        $id = $entity->getId();
        if (!is_int($id)) {
            throw new InvalidArgumentException($entity::class . " object id is not an int");
        }
        return $id;
    }

    protected function validateName(?object $entity = null): string
    {
        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf("Entity provided is null"));
        }
        if (!method_exists($entity, 'getName')) {
            throw new InvalidArgumentException(sprintf("%s doest not expose getName method", $entity::class));
        }
        if (is_null($entity->getName())) {
            throw new InvalidArgumentException($entity::class . " object getName returns null");
        }
        $name = $entity->getName();
        if (!is_string($name)) {
            throw new InvalidArgumentException($entity::class . " object id is not an int");
        }
        return $name;
    }

    protected function validateLanguage(?object $entity = null): string
    {

        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf("Entity provided is null"));
        }
        if (!method_exists($entity, 'getLanguage')) {
            throw new InvalidArgumentException(sprintf("%s doest not expose getLanguage method", $entity::class));
        }
        $language = $entity->getLanguage();

        if (!($language instanceof Language)) {
            $got = is_object($language) ? $language::class : gettype($language);
            throw new InvalidArgumentException($got . " object is not an instance of Language");
        }
        return $language->value;
    }

    protected function getCoverImageDTO(?object $entity): ?UploadedImageReadDTO
    {
        if (is_null($entity)) {
            throw new InvalidArgumentException(sprintf("Entity provided is null"));
        }
        if (!method_exists($entity, 'getCoverImage')) {
            throw new InvalidArgumentException(sprintf("%s doest not expose getCoverImage method", $entity::class));
        }

        $coverImage = $entity->getCoverImage();

        if (!($coverImage instanceof UploadedImage) && !is_null($coverImage)) {
            throw new InvalidArgumentException($entity::class . " object is not an instance of UploadedImage");
        }

        if (!is_null($coverImage)) {
            $coverImage = $this->imageDTOFactory->readDtoFromEntity($coverImage);
        }

        return $coverImage;
    }


    /**
     * @param InputBag<scalar> $inputBag
     */
    protected function getIdInput(InputBag $inputBag): ?int
    {
        if (!$inputBag->has('id') || is_null($inputBag->get('id'))) {
            return null;
        }
        $id = $inputBag->get('id');
        if (!is_int($id) && !is_string($id)) {
            throw new InvalidArgumentException('Id must be a string or an integer');
        }
        return (int) $id;
    }

    protected function getCoverImageFile(?FileBag $files): ?UploadedFile
    {
        if (is_null($files) || !$files->has('coverImageFile')) {
            return null;
        }

        $coverImageFile = $files->get('coverImageFile');
        if (!($coverImageFile instanceof UploadedFile)) {
            throw new InvalidArgumentException('The coverImageFile is not an instance of UploadedFile');
        }
        return $coverImageFile;
    }

    /**
     * @return array<UploadedFile>|null
     */
    protected function getUploadedImagesFiles(?FileBag $files): ?array
    {
        if (is_null($files) || !$files->has('uploadedImagesFiles')) {
            return null;
        }

        $uploadedImagesFiles = $files->get('uploadedImagesFiles');
        if ($uploadedImagesFiles instanceof UploadedFile) {
            return [$uploadedImagesFiles];
        }
        if (!is_iterable($uploadedImagesFiles)) {
            throw new InvalidArgumentException('FileBag uploadedImagesFiles is not an UploadedImage nor an UploadedImage[]');
        }
        $data = [];
        foreach ($uploadedImagesFiles as $file) {
            if ($file instanceof UploadedFile) {
                $data[] = $file;
            } else {
                throw new InvalidArgumentException('One of the provided uploadedImagesFiles is not an UploadedFile');
            }
        }
        return $data;
    }

    /**
     * @param InputBag<scalar> $inputBag
     */
    protected function getFloat(InputBag $inputBag, string $key, ?float $default = null): ?float
    {
        $value = $inputBag->get($key);
        if (is_null($value)) {
            return $default;
        }
        if (!is_float($value) && !is_int($value) && !is_string($value)) {
            return $default;
        }
        return (float) $value;
    }

    protected function getInt(InputBag $inputBag, string $key, ?int $default = null): ?int
    {
        $value = $inputBag->get($key);
        if ($value === null) {
            return $default;
        }

        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            return $default;
        }

        if (is_int($value)) {
            return $value;
        }

        $normalized = is_string($value) ? str_replace(',', '.', trim($value)) : (string) $value;
        if (!is_numeric($normalized)) {
            return $default;
        }

        $floatValue = (float) $normalized;

        $isMajorUnit = \str_contains($normalized, '.') || (is_float($value) && abs($floatValue - round($floatValue)) > 0.00001);

        return $isMajorUnit ? (int) round($floatValue * 100) : (int) round($floatValue);
    }

    /**
     * @param InputBag<scalar> $inputBag
     * @param array<mixed> $default
     * @return array<mixed> 
     */
    protected function getArray(InputBag $inputBag, string $key, ?array $default = null): ?array
    {
        $value = $inputBag->all($key);
        return $value !== [] ? $value : null;
    }
}
