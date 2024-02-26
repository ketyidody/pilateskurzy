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
use Symfony\Component\HttpFoundation\Response;
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
            'e.id',
            'e.dateTime',
            'e.duration',
            'e.capacity',
            'e.name',
            'e.description',
            'e.price',
            'et.name as event_type',
        ])
            ->from(Event::class, 'e')
            ->join('e.eventType', 'et')
            ->where($qb->expr()->between('e.dateTime', ':fromDate', ':toDate'))
            ->setParameters([
                'fromDate' => '2024-02-04',
                'toDate' => '2024-02-30',
            ]);

        return $this->json($res->getQuery()->execute());
    }

    public function registerToEvent(Request $request): JsonResponse
    {
        $eventId = $request->get('eventId');
        $webUser = $this->getUser();

        if (!$webUser instanceof WebUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not logged in'
            ]);
        }

        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        if (!$event instanceof Event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found'
            ]);
        }

        $event?->addUser($webUser);

        $this->doctrine->getManager()->persist($event);
        $this->doctrine->getManager()->flush();

        return $this->json(['status' => 'ok']);
    }

    public function getAllocationForEvent(Request $request, $eventId): JsonResponse
    {
        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        return $this->json([
            'allocation' => $event?->getUsers()->count(),
        ]);
    }

    public function getEventModal(Request $request, int $eventId): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('auth_simple_login');
        }
        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        $alreadyOnEvent = false;
        if ($event->getUsers()->contains($user)) {
            $alreadyOnEvent = true;
        }

        return $this->render('pages/partial/event-modal.html.twig', [
            'eventId' => $eventId,
            'eventName' => $event->getName(),
            'eventDescription' => $event->getDescription(),
            'eventPrice' => $event->getPrice(),
            'eventAllocation' => $event?->getUsers()->count(),
            'eventCapacity' => $event->getCapacity(),
            'eventStart' => $event->getDateTime()->format('Y-m-d H:i:s'),
            'eventEnd' => $event->getDateTime()->add(new \DateInterval('PT' .   $event->getDuration() . 'H'))->format('Y-m-d H:i:s'),
            'alreadyOnEvent' => $alreadyOnEvent,
        ]);
    }
}
