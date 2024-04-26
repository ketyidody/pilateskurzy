<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\WebUser;
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
 * @RouteResource("web_user")
 */
class WebUserController extends AbstractController implements SecuredControllerInterface, ClassResourceInterface
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
        $fieldDescriptors = $this->fieldDescriptorFactory->getFieldDescriptors(WebUser::RESOURCE_KEY) ?? [];
        $listBuilder = $this->listBuilderFactory->create(WebUser::class);
        $this->restHelper->initializeListBuilder($listBuilder, $fieldDescriptors);

        $listRepresentation = new PaginatedRepresentation(
            $listBuilder->execute(),
            WebUser::RESOURCE_KEY,
            (int) $listBuilder->getCurrentPage(),
            (int) $listBuilder->getLimit(),
            (int) $listBuilder->count(),
        );

        return $this->viewHandler->handle(View::create($listRepresentation));
    }

    protected function entityCollectionToArray(array $entities): array
    {
        $result = [];

        /** @var WebUser $entity */
        foreach ($entities as $entity) {
            $result[] = [
                'id' => $entity->getId(),
                'email' => $entity->getEmail(),
                'firstName' => $entity->getFirstName(),
                'lastName' => $entity->getLastName(),
            ];
        }

        return $result;
    }

    public function getAction(int $id): Response
    {
        $webUser = $this->entityManager->getRepository(WebUser::class)->find($id);
        if (!$webUser) {
            throw new NotFoundHttpException();
        }

        return $this->json(
            $this->getDataForEntity($webUser),
            Response::HTTP_OK,
            [],
            [ObjectNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($obj) { return $obj->getId(); }],
        );
    }

    public function putAction(Request $request, int $id): Response
    {
        $webUser = $this->entityManager->getRepository(WebUser::class)->find($id);
        if (!$webUser) {
            throw new NotFoundHttpException();
        }

        $data = $request->toArray();
        $this->mapDataToEntity($data, $webUser);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($webUser));
    }

    public function postAction(Request $request): Response
    {
        $entityManager = $this->entityManager;
        $webUser = new WebUser($entityManager);

        $data = $request->toArray();
        $this->mapDataToEntity($data, $webUser);
        $this->entityManager->persist($webUser);
        $this->entityManager->flush();

        return $this->json($this->getDataForEntity($webUser), 201);
    }

    public function deleteAction(int $id): Response
    {
        /** @var WebUser $WebUser */
        $WebUser = $this->entityManager->getReference(WebUser::class, $id);
        $this->entityManager->remove($WebUser);
        $this->entityManager->flush();

        return $this->json(null, 204);
    }

    protected function mapDataToEntity(array $data, WebUser $entity): void
    {
        $entity->setEmail($data['email']);
        $entity->setFirstName($data['firstName']);
        $entity->setLastName($data['lastName']);
    }

    protected function getDataForEntity(WebUser $entity): array
    {
        return [
            'id' => $entity->getId(),
            'email' => $entity->getEmail(),
            'firstName' => $entity->getFirstName(),
            'lastName' => $entity->getLastName(),
        ];
    }

    public function getSecurityContext(): string
    {
        return WebUser::SECURITY_CONTEXT;
    }

    public function getLocale(Request $request): ?string
    {
        return $request->query->get('locale');
    }
}
