<?php

namespace App\Mapper;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerInterface;
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
    /**
     * @var array<string, mixed>
     */
    protected array $data = [];
    /**
     * @var TEntity $entity
     */
    protected object $entity;

    protected NormalizerInterface $normalizer;
    protected DenormalizerInterface $denormalizer;
    protected LoggerInterface $logger;
    protected EntityManagerInterface $em;

    public function __construct(
        protected ContainerInterface $container,
        ?NormalizerInterface $norm = null,
        ?DenormalizerInterface $denorm = null,
        ?LoggerInterface $log = null,
        ?EntityManagerInterface $entitymngr = null,
    ) {
        /** @var NormalizerInterface $normalizer */
        $normalizer = $norm ?? $container->get(NormalizerInterface::class);
        /** @var DenormalizerInterface $denormalizer */
        $denormalizer = $denorm ?? $container->get(DenormalizerInterface::class);
        /** @var LoggerInterface $logger */
        $logger = $log ?? $container->get(LoggerInterface::class);
        /** @var EntityManagerInterface $em */
        $em = $entitymngr ?? $container->get(EntityManagerInterface::class);

        $this->normalizer = $normalizer;
        $this->denormalizer = $denormalizer;
        $this->logger = $logger;
        $this->em = $em;
    }

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
            if (!(is_scalar($id) || is_null($id))) {
                throw new InvalidArgumentException('Id must be an integer');
            }
            return $id !== null ? (int) $id : null;
        }
        if (method_exists($dto, 'getId')) {
            $id = $dto->getId();
            if (!(is_scalar($id) || is_null($id))) {
                throw new InvalidArgumentException('Id must be an integer');
            }
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
        if (!(is_scalar($id) || is_null($id))) {
            throw new InvalidArgumentException('Id must be an integer');
        }
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
     * @param array<mixed> $extra 
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

        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($dto, 'array');
        $this->data = $normalized;
        foreach ($this->getIgnoredFields() as $field) {
            $this->logger->warning(sprintf('unsetting field %s', $field));
            unset($this->data[$field]);
        }

        /** @var TEntity $entity */
        $entity = $this->denormalizeToEntity($this->data, $entity, $extra);
        // if ($entity::class !== $this->getEntityClass()) {
        //     throw new LogicException('The denormalized object is not an instance of ' . $this->getEntityClass());
        // }
        $entity = $this->afterDenormalization($dto, $entity, $extra);
        $this->entity = $entity;
        return $entity;
    }

    /**
     * @param array<string, mixed> $data
     * @param array<mixed> $extra
     * @param TEntity $entity
     * @return TEntity
     */
    protected function denormalizeToEntity(
        array $data,
        ?object $entity = null,
        array $extra = []
    ) {
        if (is_null($entity)) {
            $entity = $this->instantiateEntity();
        }
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
     * @param array<mixed> $extra
     * @return TEntity
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
