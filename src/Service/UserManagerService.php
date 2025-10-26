<?php

namespace App\Service;

use App\DTO\User\UserDTOFactory;
use App\Entity\User;
use App\Mapper\UserEntityMapper;
use App\Repository\UserRepository;
use App\Security\Role;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserManagerService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private UserEntityMapper $userMapper,
        private UserDTOFactory $dtoFactory,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
        private ValidatorInterface $validator,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param InputBag<scalar> $inputBag
     */
    public function updateUser(InputBag $inputBag): User
    {
        $dto = $this->dtoFactory->writeDtoFromInputBag($inputBag);
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            throw new ValidationFailedException($dto, $violations);
        }

        if (is_null($dto->id)) {
            throw new InvalidArgumentException('Update user : id is null');
        }

        /** @var User|null $user */
        $user = $this->userRepository->find($dto->id);
        if (is_null($user)) {
            throw new ResourceNotFoundException('No user was found for id ' . $dto->id);
        }
        if ($user->isDeleted()) {
            throw new ResourceNotFoundException('No user was found for id ' . $dto->id);
        }

        $currentUser = $this->security->getUser();
        if (!($currentUser instanceof User)) {
            throw new AccessDeniedException('No authenticated user found');
        }

        if ($currentUser->getId() !== $user->getId() && !$this->security->isGranted(Role::ADMIN->value)) {
            throw new AccessDeniedException('Connected user does not have the right to update this user');
        }

        if (!is_null($dto->password)) {
            $dto->password = $this->passwordHasher->hashPassword($user, $dto->password);
        } else {
            $dto->password = $user->getPassword();
        }

        if (is_null($dto->pseudo)) {
            $dto->pseudo = $user->getPseudo();
        }

        if (is_null($dto->roles)) {
            $dto->roles = $user->getRoles();
        }

        if (is_null($dto->googleSub)) {
            $dto->googleSub = $user->getGoogleSub();
        }

        if (is_null($dto->emailVerified)) {
            $dto->emailVerified = $user->getEmailVerified();
        }

        $this->logger->debug(sprintf('Updating user %d', $dto->id));

        /** @var User $user */
        $user = $this->userMapper->fromWriteDTO($dto, $user);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function removeUser(int $userId, bool $hardDelete = false): void
    {
        $currentUser = $this->security->getUser();
        if (!($currentUser instanceof User)) {
            throw new AccessDeniedException('No authenticated user found');
        }

        if ($currentUser->getId() !== $userId && !$this->security->isGranted(Role::ADMIN->value)) {
            throw new AccessDeniedException('Connected user does not have the right to remove this user');
        }

        /** @var User|null $user */
        $user = $this->userRepository->find($userId);
        if (is_null($user)) {
            throw new ResourceNotFoundException('No user was found for id ' . $userId);
        }
        if ($user->isDeleted() && !$hardDelete) {
            throw new ResourceNotFoundException('No user was found for id ' . $userId);
        }

        $this->logger->debug(sprintf('Removing user %d', $userId));

        if ($hardDelete) {
            if (!$this->security->isGranted(Role::ADMIN->value)) {
                throw new AccessDeniedException('Hard delete requires administrator role');
            }
            $this->entityManager->remove($user);
        } else {
            $user->markAsDeleted();
            $this->entityManager->persist($user);
        }
        $this->entityManager->flush();
    }

    /**
     * @return list<User>
     */
    public function getAllUsers(): array
    {
        if (!$this->security->isGranted(Role::ADMIN->value)) {
            throw new AccessDeniedException('Only administrators can list every user');
        }

        $queryBuilder = $this->userRepository->createQueryBuilder('user')
            ->andWhere('user.deletedAt IS NULL')
            ->orderBy('user.id', 'ASC');

        /** @var list<User> $users */
        $users = $queryBuilder->getQuery()->getResult();

        return $users;
    }
}
