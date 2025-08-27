<?php

namespace App\Mapper;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @template TEntity of object
 * @template TDTO of object
 */

abstract class AbstractEntityMapper
{

    protected array $data = [];
    /**
     * @var TEntity $entity
     */
    protected object $entity;

    public function __construct(
        protected NormalizerInterface $normalizer,
        protected DenormalizerInterface $denormalizer,
        protected EntityManagerInterface $em,
        private ?LoggerInterface $logger = null,
    ) {}

    /**
     * Should return the class-string of the target entity
     * 
     * @return class-string<TEntity>
     */
    abstract protected function getEntityClass(): string;

    /**
     * Should create a new instance oof the target entity in case no entity is provided
     * @return TEntity
     */
    abstract protected function instantiateEntity(): object;

    /**
     * Fields to ignore during normalization and denormalization (DTO keys)
     * @return string[]
     */
    protected function getIgnoredFields(): array
    {
        return ['id'];
    }

    /**
     * Extract the id from the DTO. Assumes public property or getId() accessor.
     * @param TDTO $dto
     */
    protected function getIdFromDTO(object $dto): ?int
    {
        if (property_exists($dto, 'id')) {
            $id = $dto->id;
            return $id !== null ? (int) $id : null;
        }
        if (method_exists($dto, 'getId')) {
            $id = $dto->getId();
            return $id !== null ? (int) $id : null;
        }
        $this->logger->warning('AbstractEntityMapper : id is null or no accessor was found on ' . $this->getEntityClass());
        return null;
    }

    /**
     * Extract the Id from the entity and fails if not getId accessor is provided
     * @throws InvalidArgumentException
     * @param TEntity $entity
     */
    private function getEntityId(object $entity): ?int
    {
        if (!method_exists($entity, 'getId')) {
            throw new InvalidArgumentException('The provided entity has no getId accessor');
        }
        $id = $entity->getId();
        return is_null($id) ? null : (int) $id;
    }

    /**
     * @param TEntity $entity
     */
    private function assertEntityOfClass(object $entity, string $class): void
    {
        if (!is_a($entity, $class)) {
            throw new InvalidArgumentException(sprintf(
                'Expected entity of type %s, got %s',
                $class,
                $entity::class
            ));
        }
    }

    /**
     * @param TEntity $entity
     * @param TDTO $dto
     */
    public function fromWriteDTO(object $dto, ?object $entity = null, array $extra = []): object
    {
        $entityClass = $this->getEntityClass();
        $dtoId = $this->getIdFromDTO($dto);

        if (!is_null($entity)) {
            $this->assertEntityOfClass($entity, $entityClass);
            $entityId = $this->getEntityId($entity);

            if (!is_null($dtoId) && !is_null($entityId) && $dtoId !== $entityId) {
                throw new InvalidArgumentException(sprintf(
                    'Provided DTO id %s does not match provided %s id %s',
                    $dtoId,
                    (new \ReflectionClass($entity))->getShortName(),
                    $entityId
                ));
            }
        } elseif (!is_null($dtoId)) {
            $entity = $this->em->find($entityClass, $dtoId);
            if (is_null($entity)) {
                throw new InvalidArgumentException(sprintf(
                    'No %s was found for id %s',
                    (new ReflectionClass($entityClass))->getShortName(),
                    $dtoId
                ));
            }
        } else {
            $entity = $this->instantiateEntity();
        }
        /** @var TEntity $entity */
        $this->entity = $entity;

        $this->data = $this->normalizer->normalize($dto, 'array');
        foreach ($this->getIgnoredFields() as $field) {
            $this->logger->warning(sprintf('unsetting field %s', $field));
            unset($this->data[$field]);
        }

        $entity = $this->denormalizeToEntity($this->data, $entity, $extra);
        $entity = $this->afterDenormalization($dto, $entity, $extra);
        $this->entity = $entity;
        return $entity;
    }

    /**
     * @param TEntity $entity
     * @param TDTO $dto
     */
    protected function denormalizeToEntity(
        array $data,
        ?object $entity = null,
        array $extra = []
    ) {
        $this->denormalizer->denormalize(
            $this->data,
            $this->getEntityClass(),
            'array',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $entity,
                AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => false,
                AbstractNormalizer::IGNORED_ATTRIBUTES => $this->getIgnoredFields(),
            ]
        );
        return $entity;
    }

    /**
     * Applies additional operations after denormalization. Default does no-op.
     * @param TEntity $entity
     * @param TDTO $dto
     */
    protected function afterDenormalization(
        object $dto,
        object $entity,
        array $extra = []
    ): object {
        $this->logger->critical("Afdtrer is called here");
        if (array_key_exists('coverImage', $extra)) {
            $this->logger->critical("The case is coverImage provided");
            if (method_exists($entity, 'setCoverImage')) {
                $entity->setCoverImage($extra['coverImage']);
            } else {
                throw new InvalidArgumentException(sprintf('Entity %s does not support cover image but coverImage was provided in extra', $entity::class));
            }
        }
        return $entity;
    }
}
