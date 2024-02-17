<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectManagerAware;

#[ORM\Entity]
class Event implements ObjectManagerAware
{
    public const RESOURCE_KEY = 'event';
    public const SECURITY_CONTEXT = 'sulu.event.event';

    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\GeneratedValue]
    protected int $id;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected \DateTime $dateTime;

    #[ORM\Column(type: Types::TEXT)]
    protected $name;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $duration;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $capacity;

    #[ORM\ManyToOne(targetEntity: EventType::class)]
    #[ORM\JoinColumn(name: 'event_type_id', referencedColumnName: 'id')]
    protected ?EventType $eventType;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'events')]
    protected Collection $attendee;

    protected ?ObjectManager $entityManager = null;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->attendee = new ArrayCollection();
        $this->entityManager = $entityManager;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param mixed $dateTime
     */
    public function setDateTime($dateTime): void
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return mixed
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param mixed $duration
     */
    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @return mixed
     */
    public function getCapacity()
    {
        return $this->capacity;
    }

    /**
     * @param mixed $capacity
     */
    public function setCapacity($capacity): void
    {
        $this->capacity = $capacity;
    }

    /**
     * @return mixed
     */
    public function getAttendee()
    {
        return $this->attendee;
    }

    /**
     * @param mixed $attendee
     */
    public function setAttendee($attendee): void
    {
        $this->attendee = $attendee;
    }

    public function addAttendee(User $attendee): void
    {
        $attendee->addEvent($this);
        $this->attendee->add($attendee);
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getEventType()
    {
        return $this->entityManager?->getRepository(EventType::class)->findOneBy(['id' => $this->eventType]);
    }

    /**
     * @param mixed $eventType
     */
    public function setEventType($eventTypeId): void
    {
        $eventType = $this->entityManager?->getRepository(EventType::class)->findOneBy(['id' => $eventTypeId]);
        $this->eventType = $eventType;
    }

    public function injectObjectManager(ObjectManager $objectManager, ClassMetadata $classMetadata)
    {
        $this->entityManager = $objectManager;
    }

    public function save()
    {
        $this->entityManager->persist($this);
        $this->entityManager->flush($this);
    }
}
