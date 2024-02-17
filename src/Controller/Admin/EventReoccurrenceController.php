<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EventReoccurrence;
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

/**
 * @RouteResource("eventreoccurrence")
 */
class EventReoccurrenceController extends AbstractController implements SecuredControllerInterface, ClassResourceInterface
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
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(EventReoccurrence::RESOURCE_KEY) ?? [];
        $listBuilder = $this->listBuilderFactory->create(EventReoccurrence::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            EventReoccurrence::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count(),
        );

        return $this->viewHandler->handle(View::create($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $eventReoccurrence = $this->entityManager->getRepository(EventReoccurrence::class)->find($id);
        if (!$eventReoccurrence) {
            throw new NotFoundHttpException();
        }

        return $this->json($this->getDataForEntity($eventReoccurrence));
    }

    public function putAction(Request $request, int $id): Response
    {
        $eventReoccurrence = $this->entityManager->getRepository(EventReoccurrence::class)->find($id);
        if (!$eventReoccurrence) {
            throw new NotFoundHttpException();
        }

        $data = $request->toArray();
        $this->mapDataToEntity($data, $eventReoccurrence);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($eventReoccurrence));
    }

    public function postAction(Request $request): Response
    {
        $eventReoccurrence = new EventReoccurrence();

        $data = $request->toArray();
        $this->mapDataToEntity($data, $eventReoccurrence);
        $this->entityManager->persist($eventReoccurrence);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($eventReoccurrence), 201);
    }

    public function deleteAction(int $id): Response
    {
        /** @var EventReoccurrence $eventReoccurrence */
        $eventReoccurrence = $this->entityManager->getReference(EventReoccurrence::class, $id);
        $this->entityManager->remove($eventReoccurrence);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    protected function getDataForEntity(EventReoccurrence $entity): array
    {
        $dateTime = $entity->getDateTime();

        return [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
            'dateTime' => $dateTime ? $dateTime->format('c') : null,
            'duration' => $entity->getDuration(),
            'frequency' => $entity->getFrequency(),
        ];
    }

    protected function mapDataToEntity(array $data, EventReoccurrence $entity): void
    {
        $entity->setName($data['name']);
        $entity->setDateTime(new \DateTime($data['dateTime']));
        $entity->setFrequency($data['frequency']);
        $entity->setDuration($data['duration']);
    }

    public function getSecurityContext(): string
    {
        return EventReoccurrence::SECURITY_CONTEXT;
    }

    public function getLocale(Request $request): ?string
    {
        return $request->query->get('locale');
    }
}
