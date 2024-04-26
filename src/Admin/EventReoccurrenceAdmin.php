<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\EventReoccurrence;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class EventReoccurrenceAdmin extends Admin
{
    public const EVENT_REPCCURRENCE_FORM_KEY = 'event_reoccurrence_details';
    public const EVENT_REPCCURRENCE_LIST_VIEW = 'app.event_reoccurrence_list';
    public const EVENT_REPCCURRENCE_ADD_FORM_VIEW = 'app.event_reoccurrence_add_form';
    public const EVENT_REPCCURRENCE_EDIT_FORM_VIEW = 'app.event_reoccurrence_edit_form';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
//        if ($this->securityChecker->hasPermission(EventReoccurrence::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
//            $rootNavigationItem = new NavigationItem('app.event_reoccurrences');
//            $rootNavigationItem->setView(static::EVENT_REPCCURRENCE_LIST_VIEW);
//            $rootNavigationItem->setIcon('su-calendar');
//            $rootNavigationItem->setPosition(30);
//
//            $navigationItemCollection->add($rootNavigationItem);
//        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $listView = $this->viewBuilderFactory->createListViewBuilder(static::EVENT_REPCCURRENCE_LIST_VIEW, '/eventreoccurrences')
            ->setResourceKey(EventReoccurrence::RESOURCE_KEY)
            ->setListKey('event_reoccurrence')
            ->addToolbarActions([new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')])
            ->setAddView(static::EVENT_REPCCURRENCE_ADD_FORM_VIEW)
            ->setEditView(static::EVENT_REPCCURRENCE_EDIT_FORM_VIEW)
            ->addListAdapters(['table']);

        $viewCollection->add($listView);

        $addFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::EVENT_REPCCURRENCE_ADD_FORM_VIEW, '/eventreoccurrences/add')
            ->setResourceKey(EventReoccurrence::RESOURCE_KEY)
            ->setBackView(static::EVENT_REPCCURRENCE_LIST_VIEW);

        $viewCollection->add($addFormView);

        $addDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::EVENT_REPCCURRENCE_ADD_FORM_VIEW . '.details', '/details')
            ->setResourceKey(EventReoccurrence::RESOURCE_KEY)
            ->setFormKey(static::EVENT_REPCCURRENCE_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->setEditView(static::EVENT_REPCCURRENCE_EDIT_FORM_VIEW)
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::EVENT_REPCCURRENCE_ADD_FORM_VIEW);

        $viewCollection->add($addDetailsFormView);

        $editFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::EVENT_REPCCURRENCE_EDIT_FORM_VIEW, '/eventreoccurrences/:id')
            ->setResourceKey(EventReoccurrence::RESOURCE_KEY)
            ->setBackView(static::EVENT_REPCCURRENCE_LIST_VIEW);

        $viewCollection->add($editFormView);

        $editDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::EVENT_REPCCURRENCE_EDIT_FORM_VIEW . '.details', '/details')
            ->setResourceKey(EventReoccurrence::RESOURCE_KEY)
            ->setFormKey(static::EVENT_REPCCURRENCE_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::EVENT_REPCCURRENCE_EDIT_FORM_VIEW);

        $viewCollection->add($editDetailsFormView);
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'EventReoccurrences' => [
                    EventReoccurrence::SECURITY_CONTEXT => [
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
