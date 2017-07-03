<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Drupal\Calista\Portlet\AbstractPortlet;
use MakinaCorpus\Ucms\User\UserAccess;

/**
 * User portlet display for user administrators.
 */
class UsersPortlet extends AbstractPortlet
{
    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->t("Users");
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return 'admin/dashboard/user';
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [
            new Action($this->t("Create user"), 'admin/dashboard/user/add', null, 'user', 0, true, true),
        ];
    }

    public function getContent()
    {
        return $this->renderPage('ucms_user.admin.datasource', 'module:ucms_user:Portlet/page-users.html.twig');
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted()
    {
        return $this->authorizationChecker->isGranted(UserAccess::PERM_MANAGE_ALL);
    }
}
