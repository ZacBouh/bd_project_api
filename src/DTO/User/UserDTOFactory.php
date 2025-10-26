<?php

namespace App\DTO\User;

use App\DTO\Builder\AbstractDTOFactory;
use App\Entity\User;
use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @extends AbstractDTOFactory<User, UserReadDTO, UserWriteDTO>
 */
class UserDTOFactory extends AbstractDTOFactory
{
    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $password = trim($inputBag->getString('password'));
        $googleSub = trim($inputBag->getString('googleSub'));
        $emailVerified = null;
        $rawEmailVerified = $inputBag->get('emailVerified');
        if ($rawEmailVerified !== null && $rawEmailVerified !== '') {
            if (is_array($rawEmailVerified)) {
                throw new InvalidArgumentException('emailVerified must be a boolean value');
            }

            $normalizedEmailVerified = filter_var($rawEmailVerified, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if (!is_bool($normalizedEmailVerified)) {
                throw new InvalidArgumentException('emailVerified must be a boolean value');
            }

            $emailVerified = $normalizedEmailVerified;
        } elseif ($rawEmailVerified === '' && $inputBag->has('emailVerified')) {
            $emailVerified = null;
        }

        $dto = new UserWriteDTO(
            $this->getIdInput($inputBag),
            $inputBag->getString('pseudo') !== '' ? $inputBag->getString('pseudo') : null,
            $inputBag->getString('email'),
            $password !== '' ? $password : null,
            $this->getArray($inputBag, 'roles'),
            $googleSub !== '' ? $googleSub : null,
            $emailVerified,
        );

        return $dto;
    }

    public function readDtoFromEntity(object $entity): object
    {
        $id = $this->validateId($entity);

        if (!method_exists($entity, 'getPseudo')) {
            throw new \InvalidArgumentException('User entity must expose a pseudo accessor');
        }
        $pseudo = $entity->getPseudo();
        if (!is_string($pseudo)) {
            throw new \InvalidArgumentException('User pseudo must be a string');
        }

        if (!method_exists($entity, 'getEmail')) {
            throw new \InvalidArgumentException('User entity must expose an email accessor');
        }
        $email = $entity->getEmail();
        if (!is_string($email)) {
            throw new \InvalidArgumentException('User email must be a string');
        }

        /** @var User $entity */
        $dto = new UserReadDTO(
            $id,
            $pseudo,
            $email,
            $entity->getRoles(),
            $entity->getGoogleSub(),
            $entity->getEmailVerified(),
            $entity->getCreatedAt()->format(DateTimeInterface::ATOM),
            $entity->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );

        return $dto;
    }
}
