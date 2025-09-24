<?php

namespace App\Mapper;

use App\Entity\Publisher;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use App\Entity\Title;
use Doctrine\ORM\EntityNotFoundException;
use LogicException;
use Symfony\Contracts\Service\Attribute\Required;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\Attribute\SubscribedService;

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

    public function __construct() {}
    // public function __construct(
    //     ?NormalizerInterface $norm = null,
    //     ?DenormalizerInterface $denorm = null,
    //     ?LoggerInterface $log = null,
    //     ?EntityManagerInterface $entitymngr = null,
    // ) {
    //     if (!is_null($norm)) {
    //         $this->normalizer = $norm;
    //     }
    //     if (!is_null($denorm)) {
    //         $this->denormalizer = $denorm;
    //     }
    //     if (!is_null($log)) {
    //         $this->logger = $log;
    //     }
    //     if (!is_null($entitymngr)) {
    //         $this->em = $entitymngr;
    //     }
    // }

    #[Required]
    public function setDependencies(
        NormalizerInterface $normalizer,
        DenormalizerInterface $denormalizer,
        LoggerInterface $logger,
        EntityManagerInterface $em,
    ): void {
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
     * Should create a new instance of the target entity in case no entity is provided by the consumer.
     * @return TEntity
     */
    abstract protected function instantiateEntity(): object;

    /**
     * Fields to ignore during denormalization step Array => Entity (DTO keys)
     * @return string[]
     */
    protected function getDenormalizerIgnoredFields(): array
    {
        return ['id'];
    }
    /**
     * Fields to ignore during denormalization step DTO => Array (DTO keys)
     * @return string[]
     */
    protected function getNormalizerIgnoredFields(): array
    {
        return ['id', 'coverImageFile', 'uploadedImages',];
    }

    /**
     * Callbacks used for the DTO => Array step.
     * @return array<callable>
     */
    protected function getNormalizerCallbacks(): array
    {
        return [
            'titleIds' => function (array $titlesIds) {
                $titleRefs = [];
                foreach ($titlesIds as $titleId) {
                    $ref = $this->em->getReference(Title::class, $titleId);
                    if (!is_null($ref)) {
                        $titleRefs[] = $ref;
                    }
                }
                $this->data['titles'] = $titleRefs;
            },
            'publisherId' => function (string $publisherId) {
                $ref = $this->em->getReference(Publisher::class, $publisherId);
                if (!is_null($ref)) {
                    $this->data['publisher'] = $ref;
                }
            }
        ];
    }

    /**
     * Callbacks used for the Array => Entity step.
     * @return array<callable>
     */
    protected function getDenormalizerCallbacks(): array
    {
        return [];
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
     * 
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
     * Creates or updates an entity based on a dto.
     * The steps it follows is DTO -> (Normalized) -> Array -> (Denormalization) -> Entity 
     * @param TEntity|null $entity
     * @param TDTO $dto
     * @param array<mixed> $extra 
     * @return  TEntity
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
            $this->logger->debug('Created new Copy instance');
        }
        /** @var TEntity $entity */
        $this->entity = $entity;

        /** @var array<string, mixed> $normalized */
        $normalized = $this->normalizer->normalize($dto, 'array', [
            AbstractObjectNormalizer::CALLBACKS => $this->getNormalizerCallbacks(),
            AbstractObjectNormalizer::IGNORED_ATTRIBUTES => $this->getNormalizerIgnoredFields(),
        ]);
        $this->data = $normalized;
        $this->logger->debug('Normalized WriteDTO');
        foreach ($this->getNormalizerIgnoredFields() as $field) {
            $this->logger->warning(sprintf('unsetting field %s', $field));
            unset($this->data[$field]);
        }

        /** @var TEntity $entity */
        $entity = $this->denormalizeToEntity($this->data, $entity, $extra);
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
                AbstractNormalizer::ALLOW_EXTRA_ATTRIBUTES => true,
                AbstractNormalizer::IGNORED_ATTRIBUTES => $this->getDenormalizerIgnoredFields(),
                AbstractNormalizer::CALLBACKS => $this->getDenormalizerCallbacks(),
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
        if (array_key_exists('coverImage', $extra)) {
            if (method_exists($entity, 'setCoverImage')) {
                $entity->setCoverImage($extra['coverImage']);
            } else {
                throw new InvalidArgumentException(sprintf('Entity %s does not support cover image but coverImage was provided in extra', $entity::class));
            }
        }
        return $entity;
    }

    /**
     * @template TEntityReference of object
     * @param class-string<TEntityReference> $entityClass
     * @param int|string $id
     * @return TEntityReference
     */
    public function checkEntityExists(string $entityClass, int|string $id): object
    {
        $this->logger->warning(sprintf("Checking existence in db for : %s with id %s", $entityClass, $id));
        $repo = $this->em->getRepository($entityClass);

        $result =  $repo->createQueryBuilder('e')
            ->select('1')
            ->andWhere('e.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
        if (!$result) {
            throw new EntityNotFoundException('Abstract Mapper : checkEntityExists no ' . $entityClass . ' found for id ' . $id);
        }

        $ref = $this->em->getReference($entityClass, $id);
        if (is_null($ref)) {
            throw new LogicException(sprintf('The %s with id %s exists in the db but the ref from entity manager is null', $entityClass, $id));
        }
        return $ref;
    }
}
