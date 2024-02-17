<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\EventType;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class EventTypeAdmin extends Admin
{
    public const EVENT_TYPE_FORM_KEY = 'event_type_details';
    public const EVENT_TYPE_LIST_VIEW = 'app.event_type_list';
    public const EVENT_TYPE_ADD_FORM_VIEW = 'app.event_type_add_form';
    public const EVENT_TYPE_EDIT_FORM_VIEW = 'app.event_type_edit_form';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(EventType::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $eventTypeNavigationItem = new NavigationItem('app.event_type');
            $eventTypeNavigationItem->setView(static::EVENT_TYPE_LIST_VIEW);
            $eventTypeNavigationItem->setIcon('su-calendar');
            $eventTypeNavigationItem->setPosition(30);

            $navigationItemCollection->add($eventTypeNavigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $listView = $this->viewBuilderFactory->createListViewBuilder(static::EVENT_TYPE_LIST_VIEW, '/eventtypes')
            ->setResourceKey(EventType::RESOURCE_KEY)
            ->setListKey('event_type')
            ->addToolbarActions([new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')])
            ->setAddView(static::EVENT_TYPE_ADD_FORM_VIEW)
            ->setEditView(static::EVENT_TYPE_EDIT_FORM_VIEW)
            ->addListAdapters(['table']);

        $viewCollection->add($listView);

        $addFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::EVENT_TYPE_ADD_FORM_VIEW, '/eventtypes/add')
            ->setResourceKey(EventType::RESOURCE_KEY)
            ->setBackView(static::EVENT_TYPE_LIST_VIEW);

        $viewCollection->add($addFormView);

        $addDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::EVENT_TYPE_ADD_FORM_VIEW . '.details', '/details')
            ->setResourceKey(EventType::RESOURCE_KEY)
            ->setFormKey(static::EVENT_TYPE_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->setEditView(static::EVENT_TYPE_EDIT_FORM_VIEW)
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::EVENT_TYPE_ADD_FORM_VIEW);

        $viewCollection->add($addDetailsFormView);

        $editFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::EVENT_TYPE_EDIT_FORM_VIEW, '/eventtypes/:id')
            ->setResourceKey(EventType::RESOURCE_KEY)
            ->setBackView(static::EVENT_TYPE_LIST_VIEW);

        $viewCollection->add($editFormView);

        $editDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::EVENT_TYPE_EDIT_FORM_VIEW . '.details', '/details')
            ->setResourceKey(EventType::RESOURCE_KEY)
            ->setFormKey(static::EVENT_TYPE_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::EVENT_TYPE_EDIT_FORM_VIEW);

        $viewCollection->add($editDetailsFormView);
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'EventType' => [
                    EventType::SECURITY_CONTEXT => [
                        PermissionTypes::VIEW,
                        PermissionTypes::ADD,
                        PermissionTypes::EDIT,
                        PermissionTypes::DELETE,
                    ],
                ],
            ],
        ];
    }
}
