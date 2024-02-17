<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use FOS\RestBundle\View\ViewHandlerInterface;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactoryInterface;
use Sulu\Component\Rest\ListBuilder\Metadata\FieldDescriptorFactoryInterface;
use Sulu\Component\Rest\ListBuilder\PaginatedRepresentation;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * @RouteResource("event")
 */
class EventController extends AbstractController implements SecuredControllerInterface, ClassResourceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ViewHandlerInterface $viewHandler,
        private FieldDescriptorFactoryInterface $fieldDescriptorFactory,
        private DoctrineListBuilderFactoryInterface $listBuilderFactory,
        private RestHelperInterface $restHelper,
    ) {
    }

    public function cgetAction(): Response
    {
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(Event::RESOURCE_KEY) ?? [];
        $listBuilder = $this->listBuilderFactory->create(Event::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $events = $this->entityManager->getRepository(Event::class)->findAll();

        $listRepresentation = new PaginatedRepresentation(
            $this->entityCollectionToArray($events),
            Event::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count(),
        );

        return $this->viewHandler->handle(View::create($listRepresentation));
    }

    protected function entityCollectionToArray(array $entities): array
    {
        $result = [];

        /** @var Event $entity */
        foreach ($entities as $entity) {
            $result[] = [
                'dateTime' => $entity->getDateTime()->format('Y-m-d H:i:s'),
                'name' => $entity->getName(),
                'event_type' => $entity->getEventType()?->__toString(),
                'capacity' => $entity->getCapacity(),
                'duration' => $entity->getDuration() . 'h',
                'id' => $entity->getId(),
            ];
        }

        return $result;
    }

    public function getAction(int $id): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        if (!$event) {
            throw new NotFoundHttpException();
        }

        return $this->json(
            $this->getDataForEntity($event),
            Response::HTTP_OK,
            [],
            [ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($obj) { return $obj->getId(); }],
        );
    }

    public function putAction(Request $request, int $id): Response
    {
        $event = $this->entityManager->getRepository(Event::class)->find($id);
        if (!$event) {
            throw new NotFoundHttpException();
        }

        $data = $request->toArray();
        $this->mapDataToEntity($data, $event);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($event));
    }

    public function postAction(Request $request): Response
    {
        $entityManager = $this->entityManager;
        $event = new Event($entityManager);

        $data = $request->toArray();
        $this->mapDataToEntity($data, $event);
        $this->entityManager->persist($event);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($event), 201);
    }

    public function deleteAction(int $id): Response
    {
        /** @var Event $Event */
        $Event = $this->entityManager->getReference(Event::class, $id);
        $this->entityManager->remove($Event);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    protected function mapDataToEntity(array $data, Event $entity): void
    {
        $entity->setDateTime(new \DateTime($data['dateTime']));
        $entity->setCapacity((int) $data['capacity']);
        $entity->setDuration((int) $data['duration']);
        $entity->setName($data['name']);
        $entity->setEventType($data['event_type']);
    }

    protected function getDataForEntity(Event $entity): array
    {
        $dateTime = $entity->getDateTime();

        // Turn off attendee for now
        return [
            'id' => $entity->getId(),
            'dateTime' => $dateTime ? $dateTime->format('c') : null,
            'duration' => $entity->getDuration(),
            'capacity' => $entity->getCapacity(),
            //            'attendee' => $entity->getAttendee(),
            'name' => $entity->getName(),
            'event_type' => $entity->getEventType(),
        ];
    }

    public function getSecurityContext(): string
    {
        return Event::SECURITY_CONTEXT;
    }

    public function getLocale(Request $request): ?string
    {
        return $request->query->get('locale');
    }
}
