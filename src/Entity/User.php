<?php

namespace App\Entity;

use App\Entity\Trait\HasDefaultNormalizeCallback;
use App\Entity\Trait\SoftDeletableTrait;
use App\Entity\Trait\TimestampableTrait;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableTrait;
    use SoftDeletableTrait;
    /** @use HasDefaultNormalizeCallback<self> */
    use HasDefaultNormalizeCallback;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private string $pseudo;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[ORM\Column(length: 180, type: 'string', unique: true, nullable: false)]
    #[Groups(['user:read'])]
    private string $email;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(nullable: true)]
    private ?string $googleSub = null;

    #[ORM\Column(nullable: true)]
    private ?bool $emailVerified = false;

    #[ORM\Column(nullable: true)]
    private ?string $emailVerificationToken = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $emailVerificationExpiresAt = null;

    public function getEmailVerificationExpiresAt(): ?DateTimeImmutable
    {
        return $this->emailVerificationExpiresAt;
    }

    public function setEmailVerificationExpiresAt(DateTimeImmutable|null $date): static
    {
        $this->emailVerificationExpiresAt = $date;
        return $this;
    }
    public function getEmailVerificationToken(): ?string
    {
        return $this->emailVerificationToken;
    }

    public function setEmailVerificationToken(string|null $token): static
    {
        $this->emailVerificationToken = $token;
        return $this;
    }

    public function getEmailVerified(): bool
    {
        if (is_null($this->emailVerified)) {
            return false;
        }
        return $this->emailVerified;
    }

    public function setEmailVerified(bool $emailVerified): static
    {
        $this->emailVerified = $emailVerified;
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGoogleSub(): ?string
    {
        return $this->googleSub;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setEmail(string $email): static
    {
        if ($email === "") {
            throw new \InvalidArgumentException('Email cannot be an empty string.');
        }

        $this->email = $email;

        return $this;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function setGoogleSub(string $googleSub): static
    {
        $this->googleSub = $googleSub;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     * 
     * @return non-empty-string
     */
    public function getUserIdentifier(): string
    {
        if ($this->email === '') {
            throw new \LogicException('Email must not be empty broken.');
        }
        return $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        if (!is_null($this->password)) {
            $data["\0" . self::class . "\0password"] = hash('crc32c', $this->password);
        }
        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }
}
