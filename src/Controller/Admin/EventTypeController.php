<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EventType;
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
 * @RouteResource("eventtype")
 */
class EventTypeController extends AbstractController implements SecuredControllerInterface, ClassResourceInterface
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
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(EventType::RESOURCE_KEY) ?? [];
        $listBuilder = $this->listBuilderFactory->create(EventType::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            EventType::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count(),
        );

        return $this->viewHandler->handle(View::create($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $eventType = $this->entityManager->getRepository(EventType::class)->find($id);
        if (!$eventType) {
            throw new NotFoundHttpException();
        }

        return $this->json(
            $this->getDataForEntity($eventType),
            Response::HTTP_OK,
            [],
            [ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($obj) { return $obj->getId(); }],
        );
    }

    public function putAction(Request $request, int $id): Response
    {
        $eventType = $this->entityManager->getRepository(EventType::class)->find($id);
        if (!$eventType) {
            throw new NotFoundHttpException();
        }

        $data = $request->toArray();
        $this->mapDataToEntity($data, $eventType);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($eventType));
    }

    public function postAction(Request $request): Response
    {
        $eventType = new EventType();

        $data = $request->toArray();
        $this->mapDataToEntity($data, $eventType);
        $this->entityManager->persist($eventType);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($eventType), 201);
    }

    public function deleteAction(int $id): Response
    {
        /** @var EventType $eventType */
        $eventType = $this->entityManager->getReference(EventType::class, $id);
        $this->entityManager->remove($eventType);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    protected function mapDataToEntity(array $data, EventType $entity): void
    {
        $entity->setName($data['name']);
    }

    protected function getDataForEntity(EventType $entity): array
    {
        return [
            'id' => $entity->getId(),
            'name' => $entity->getName(),
        ];
    }

    public function getSecurityContext(): string
    {
        return EventType::SECURITY_CONTEXT;
    }

    public function getLocale(Request $request): ?string
    {
        return $request->query->get('locale');
    }
}
