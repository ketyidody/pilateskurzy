<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User extends \Sulu\Bundle\SecurityBundle\Entity\User
{
    #[ORM\ManyToMany(targetEntity: Event::class, inversedBy: 'attendee')]
    #[ORM\JoinTable(name: 'users_events')]
    protected Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
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
}