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

    /**
     * @deprecated
     * @param string $dateString
     * @return JsonResponse
     * @throws \Exception
     */
    public function getEventsForMonthAction(string $dateString): JsonResponse
    {
        // if it is monday, get this monday, if it is any other day, get the previous monday
        if (date('j', (new \DateTime($dateString))->getTimestamp()) === '1') {
            $start = date('Y-m-d', strtotime('monday 0 week', strtotime($dateString)));
        } else {
            $start = date('Y-m-d', strtotime('monday -1 week', strtotime($dateString)));
        }

        $end = date('Y-m-d', strtotime('sunday 0 week', strtotime($dateString)));
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
                'fromDate' => $start,
                'toDate' => $end,
            ]);

        $data = [];
        foreach ($res->getQuery()->execute() as $eventArray) {
            $event = $this->em->getRepository(Event::class)->find($eventArray['id']);
            $data[] = array_merge($eventArray, ['allocation' => count($event->getUsers())]);
        }

        return $this->json($data, Response::HTTP_OK);
    }
    
    public function getFutureEvents(int $limit, int $offset): JsonResponse
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
            ->where($qb->expr()->gt('e.dateTime', ':fromDate'))
            ->setParameters([
                'fromDate' => (new \DateTime())->format('Y-m-d'),
            ])
            ->setMaxResults($limit)
            ->setFirstResult($offset);
        ;

        $data = [];
        foreach ($res->getQuery()->execute() as $eventArray) {
            $event = $this->em->getRepository(Event::class)->find($eventArray['id']);
            $data[] = $this->renderView('pages/partial/event-row.html.twig', [
                'event' => $event,
                'allocation' => count($event->getUsers()),
            ]);
        }

        return $this->json($data, Response::HTTP_OK);
    }

    public function registerToEvent(Request $request): JsonResponse
    {
        $eventId = $request->get('eventId');
        $webUser = $this->getUser();

        if (!$webUser instanceof WebUser) {
            return $this->json([
                'status' => 'error',
                'message' => 'Not logged in'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        if (!$event instanceof Event) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event not found'
            ], Response::HTTP_FORBIDDEN);
        }

        if ($event->getCapacity() <= $event->getUsers()->count()) {
            return $this->json([
                'status' => 'error',
                'message' => 'Event is full'
            ], Response::HTTP_FORBIDDEN);
        }

        $event->addUser($webUser);

        if (!$event->getUsers()->contains($webUser)) {
            return $this->json([
                'status' => 'error',
                'message' => 'User not registered'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $this->doctrine->getManager()->persist($event);
        $this->doctrine->getManager()->flush();

        return $this->json([
            'status' => 'ok',
            'redirectUrl' => $this->redirect('/api/event-response/modal/' . $eventId),
        ], Response::HTTP_OK);
    }

    public function getAllocationForEvent(Request $request, $eventId): JsonResponse
    {
        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        return $this->json([
            'allocation' => $event?->getUsers()->count(),
        ], Response::HTTP_OK);
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

        $eventIsFull = $event->getCapacity() <= $event->getUsers()->count();

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
            'eventIsFull' => $eventIsFull,
        ]);
    }

    public function eventResponse(Request $request, int $eventId): Response
    {
        $event = $this->doctrine->getRepository(Event::class)->find($eventId);

        return $this->render('pages/partial/event-register-response-modal.html.twig', [
            'eventId' => $eventId,
            'eventName' => $event->getName(),
            'eventDescription' => $event->getDescription(),
            'eventPrice' => $event->getPrice(),
            'eventAllocation' => $event?->getUsers()->count(),
            'eventCapacity' => $event->getCapacity(),
            'eventStart' => $event->getDateTime()->format('Y-m-d H:i:s'),
            'eventEnd' => $event->getDateTime()->add(new \DateInterval('PT' .   $event->getDuration() . 'H'))->format('Y-m-d H:i:s'),
            'response' => 'ok',
            'responseText' => 'Úspešné prihlásenie',
        ]);
    }
}
