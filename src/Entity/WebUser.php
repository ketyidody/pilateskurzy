<?php

namespace App\Entity;

use App\Facade;
use App\Service\AuthMailerService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WebUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const RESOURCE_KEY = 'web_user';
    public const SECURITY_CONTEXT = 'sulu.web_user.web_user';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: Types::TEXT, unique: true)]
    protected string $email;

    #[ORM\Column(type: Types::TEXT)]
    protected string $password;

    #[ORM\Column(type: Types::TEXT)]
    protected string $firstName;

    #[ORM\Column(type: Types::TEXT)]
    protected string $lastName;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected \DateTime $passwordResetRequested;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected string $passwordResetHash;

    public function __construct() {
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function setSaltAndPassword(): void
    {
        /** @var UserPasswordHasher $passwordHasher */
        $passwordHasher = Facade::create(UserPasswordHasher::class);

        $this->password = $passwordHasher->hashPassword(
            $this,
            $this->password
        );
    }

    public function generateAndSendPasswordResetLink()
    {
        /** @var AuthMailerService $authMailerService */
        $authMailerService = Facade::create(AuthMailerService::class);
        $hash = md5(random_bytes(10));

        $this->passwordResetRequested = new \DateTime();
        $this->passwordResetHash = $hash;

        $authMailerService->sendPasswordResetEmail($this);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getRoles(): array
    {
        return ['USER'];
    }

    public function eraseCredentials()
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPasswordResetRequested(): \DateTime
    {
        return $this->passwordResetRequested;
    }

    public function setPasswordResetRequested(\DateTime $passwordResetRequested): void
    {
        $this->passwordResetRequested = $passwordResetRequested;
    }

    public function getPasswordResetHash(): string
    {
        return $this->passwordResetHash;
    }

    public function setPasswordResetHash(string $passwordResetHash): void
    {
        $this->passwordResetHash = $passwordResetHash;
    }
}