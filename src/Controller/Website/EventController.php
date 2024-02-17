<?php

declare(strict_types=1);

namespace App\Controller\Website;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use JetBrains\PhpStorm\NoReturn;
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

    public function getEventsForMonthAction(string $month): JsonResponse
    {
        $formattedResponse = [];

        $qb = $this->em->createQueryBuilder();
        $res = $qb->select([
            't.id',
            't.dateTime',
            't.duration',
            't.capacity',
            't.name',
//            't.eventType',
        ])
            ->from(Event::class, 't')
            ->where($qb->expr()->between('t.dateTime', ':fromDate', ':toDate'))
            ->setParameters([
                'fromDate' => "2024-02-04",
                'toDate' => "2024-02-16",
            ])
        ;

        return $this->json($res->getQuery()->execute());

        $events = $this->doctrine
            ->getRepository(Event::class)
            ->findBy([
                'dateTime' => ['2024-02-08 18:27:28'],
            ]);

        foreach ($events as $event) {
            $formattedResponse[$event->getId()] = [
                'date' => $event->getDateTime(),
                'eventName' => 'Event',
                'className' => 'event-event',
                'dateColor' => 'red',
            ];
        }

        return $this->json($formattedResponse);
    }

    public function registerToEvent(Request $request): JsonResponse
    {
        $eventId = $request->get('id');
        $email = $request->get('email');

        $event = $this->doctrine->getRepository(Event::class)->find($eventId);
        $user = $this->doctrine->getRepository(User::class)->findOneBy(['email' => $email]);
        dd($event);
        $event?->addAttendee($user);
        $this->doctrine->getManager()->persist($event);
        $this->doctrine->getManager()->persist($user);
        $this->doctrine->getManager()->flush();
//        $event?->save();
        return $this->json(['status' => 'ok']);
    }
}
