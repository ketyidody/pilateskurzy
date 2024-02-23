<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Entity\Event;
use App\Entity\WebUser;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteResource("web_event")
 */
class EventController extends AbstractController
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EntityManagerInterface $em,
    ) {
    }

    public function getEventsForMonthAction(Request $request): JsonResponse
    {
        $qb = $this->em->createQueryBuilder();
        $res = $qb->select([
            't.id',
            't.dateTime',
            't.duration',
            't.capacity',
            't.name',
            'et.name as event_type',
        ])
            ->from(Event::class, 't')
            ->join('t.eventType', 'et')
            ->where($qb->expr()->between('t.dateTime', ':fromDate', ':toDate'))
            ->setParameters([
                'fromDate' => '2024-02-04',
                'toDate' => '2024-02-30',
            ]);

        return $this->json($res->getQuery()->execute());
    }

    public function registerToEvent(Request $request): JsonResponse
    {
        $eventId = $request->get('id');
        $email = $request->get('email');

        $event = $this->doctrine->getRepository(Event::class)->find($eventId);
        $webUser = $this->em->getRepository(WebUser::class)
            ->findOneBy(['email' => $email]);
        if (!$webUser instanceof WebUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not logged in'
            ]);
        }
        $event?->addUser($webUser);
        $this->doctrine->getManager()->persist($event);
        $this->doctrine->getManager()->persist($webUser);
        $this->doctrine->getManager()->flush();

        // return redirect to login
        return $this->json(['status' => 'ok']);
    }

    public function getAllocationForEvent(Request $request, $eventId): JsonResponse
    {
        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        return $this->json([
            'allocation' => $event?->getUsers()->count(),
        ]);
    }
}
