<?php

declare(strict_types=1);

namespace App\Admin;

use App\Entity\WebUser;
use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItem;
use Sulu\Bundle\AdminBundle\Admin\Navigation\NavigationItemCollection;
use Sulu\Bundle\AdminBundle\Admin\View\ToolbarAction;
use Sulu\Bundle\AdminBundle\Admin\View\ViewBuilderFactoryInterface;
use Sulu\Bundle\AdminBundle\Admin\View\ViewCollection;
use Sulu\Component\Security\Authorization\PermissionTypes;
use Sulu\Component\Security\Authorization\SecurityCheckerInterface;

class WebUserAdmin extends Admin
{
    public const WEB_USER_FORM_KEY = 'web_user_details';
    public const WEB_USER_LIST_VIEW = 'app.web_user_list';
    public const WEB_USER_ADD_FORM_VIEW = 'app.web_user_add_form';
    public const WEB_USER_EDIT_FORM_VIEW = 'app.web_user_edit_form';

    public function __construct(
        private ViewBuilderFactoryInterface $viewBuilderFactory,
        private SecurityCheckerInterface $securityChecker,
    ) {
    }

    public function configureNavigationItems(NavigationItemCollection $navigationItemCollection): void
    {
        if ($this->securityChecker->hasPermission(WebUser::SECURITY_CONTEXT, PermissionTypes::EDIT)) {
            $webUserNavigationItem = new NavigationItem('app.web_users');
            $webUserNavigationItem->setView(static::WEB_USER_LIST_VIEW);
            $webUserNavigationItem->setIcon('su-user');
            $webUserNavigationItem->setPosition(30);

            $navigationItemCollection->add($webUserNavigationItem);
        }
    }

    public function configureViews(ViewCollection $viewCollection): void
    {
        $listView = $this->viewBuilderFactory->createListViewBuilder(static::WEB_USER_LIST_VIEW, '/web_users')
            ->setResourceKey(WebUser::RESOURCE_KEY)
            ->setListKey('web_user')
            ->addToolbarActions([new ToolbarAction('sulu_admin.add'), new ToolbarAction('sulu_admin.delete')])
            ->setAddView(static::WEB_USER_ADD_FORM_VIEW)
            ->setEditView(static::WEB_USER_EDIT_FORM_VIEW)
            ->addListAdapters(['table']);

        $viewCollection->add($listView);

        $addFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::WEB_USER_ADD_FORM_VIEW, '/web_users/add')
            ->setResourceKey(WebUser::RESOURCE_KEY)
            ->setBackView(static::WEB_USER_LIST_VIEW);

        $viewCollection->add($addFormView);

        $addDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::WEB_USER_ADD_FORM_VIEW . '.details', '/details')
            ->setResourceKey(WebUser::RESOURCE_KEY)
            ->setFormKey(static::WEB_USER_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->setEditView(static::WEB_USER_EDIT_FORM_VIEW)
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::WEB_USER_ADD_FORM_VIEW);

        $viewCollection->add($addDetailsFormView);

        $editFormView = $this->viewBuilderFactory->createResourceTabViewBuilder(static::WEB_USER_EDIT_FORM_VIEW, '/web_users/:id')
            ->setResourceKey(WebUser::RESOURCE_KEY)
            ->setBackView(static::WEB_USER_LIST_VIEW);

        $viewCollection->add($editFormView);

        $editDetailsFormView = $this->viewBuilderFactory->createFormViewBuilder(static::WEB_USER_EDIT_FORM_VIEW . '.details', '/details')
            ->setResourceKey(WebUser::RESOURCE_KEY)
            ->setFormKey(static::WEB_USER_FORM_KEY)
            ->setTabTitle('sulu_admin.details')
            ->addToolbarActions([new ToolbarAction('sulu_admin.save'), new ToolbarAction('sulu_admin.delete')])
            ->setParent(static::WEB_USER_EDIT_FORM_VIEW);

        $viewCollection->add($editDetailsFormView);
    }

    /**
     * @return mixed[]
     */
    public function getSecurityContexts(): array
    {
        return [
            self::SULU_ADMIN_SECURITY_SYSTEM => [
                'WebUser' => [
                    WebUser::SECURITY_CONTEXT => [
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
