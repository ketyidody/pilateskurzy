<?php

namespace App\Repository;

use App\Entity\WebUser;
use Doctrine\ORM\EntityRepository;

class EventRepository extends EntityRepository
{
    public function findByUser(WebUser $user)
    {
        $qb = $this->createQueryBuilder("e")
            ->where(':user MEMBER OF e.webUsers')
            ->setParameters(array('user' => $user))
        ;
        return $qb->getQuery()->getResult();
    }
}