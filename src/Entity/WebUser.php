<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class WebUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: Types::TEXT, unique: true)]
    protected string $email;

    #[ORM\Column(type: Types::TEXT)]
    protected string $password;

    #[ORM\Column(type: Types::TEXT)]
    protected string $salt;

    #[ORM\Column(type: Types::TEXT)]
    protected string $firstName;

    #[ORM\Column(type: Types::TEXT)]
    protected string $lastName;

    #[ORM\ManyToMany(targetEntity: Event::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'users_events')]
    protected Collection $events;

    public function __construct(protected UserPasswordHasherInterface $passwordHasher)
    {
        $this->events = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setSaltAndPassword(): void
    {
        $this->salt = sha1(random_int(10000, 99999));
        $this->password = $this->passwordHasher->hashPassword(
            $this,
            $this->password
        );
    }


    /**
     * @return mixed
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @param mixed $events
     */
    public function setEvents($events): void
    {
        $this->events = $events;
    }

    public function addEvent(Event $event)
    {
        $this->events->add($event);
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

    public function getSalt(): string
    {
        return $this->salt;
    }

    public function setSalt(string $salt): void
    {
        $this->salt = $salt;
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
}