<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\Event;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class EventAdmin extends Admin
{
    public const EVENT_FORM_KEY = 'event_details';
    public const EVENT_LIST_VIEW = 'app.event_list';
    public const EVENT_ADD_FORM_VIEW = 'app.event_add_form';
    public const EVENT_EDIT_FORM_VIEW = 'app.event_edit_form';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(Event::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $eventNavigationItem = new NavigationItem('app.events');
            $eventNavigationItem->setView(static::EVENT_LIST_VIEW);
            $eventNavigationItem->setIcon('su-calendar');
            $eventNavigationItem->setPosition(30);

            $navigationItemCollection->add($eventNavigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $listView = $this->viewBuilderFactory->createListViewBuilder(static::EVENT_LIST_VIEW, '/events')
            ->setResourceKey(Event::RESOURCE_KEY)
            ->setListKey('event')
            ->addToolbarActions([new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')])
            ->setAddView(static::EVENT_ADD_FORM_VIEW)
            ->setEditView(static::EVENT_EDIT_FORM_VIEW)
            ->addListAdapters(['table']);

        $viewCollection->add($listView);

        $addFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::EVENT_ADD_FORM_VIEW, '/events/add')
            ->setResourceKey(Event::RESOURCE_KEY)
            ->setBackView(static::EVENT_LIST_VIEW);

        $viewCollection->add($addFormView);

        $addDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::EVENT_ADD_FORM_VIEW . '.details', '/details')
            ->setResourceKey(Event::RESOURCE_KEY)
            ->setFormKey(static::EVENT_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->setEditView(static::EVENT_EDIT_FORM_VIEW)
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::EVENT_ADD_FORM_VIEW);

        $viewCollection->add($addDetailsFormView);

        $editFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::EVENT_EDIT_FORM_VIEW, '/events/:id')
            ->setResourceKey(Event::RESOURCE_KEY)
            ->setBackView(static::EVENT_LIST_VIEW);

        $viewCollection->add($editFormView);

        $editDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::EVENT_EDIT_FORM_VIEW . '.details', '/details')
            ->setResourceKey(Event::RESOURCE_KEY)
            ->setFormKey(static::EVENT_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::EVENT_EDIT_FORM_VIEW);

        $viewCollection->add($editDetailsFormView);
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'Event' => [
                    Event::SECURITY_CONTEXT => [
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
