<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Portlet\AbstractPortlet;

class AccountPortlet extends AbstractPortlet
{
    use StringTranslationTrait;

    /**
     * Return the title of this portlet.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->t("My account");
    }

    /**
     * Return the path for the main page of this portlet.
     *
     * @return null|string
     */
    public function getPath()
    {
        return null;
    }

    /**
     * @return Action[]
     */
    public function getActions()
    {
        return [
            new Action($this->t('Edit my information'), 'admin/dashboard/user/my-account', 'dialog', 'edit'),
            new Action($this->t('Edit my password'), 'admin/dashboard/user/my-password', 'dialog', 'edit'),
        ];
    }

    /**
     * Return the render array for this portlet.
     * @return array
     */
    public function getContent()
    {
        $items    = [];
        $account  = $this->getAccount();

        $items[] = [$this->t('Username'), $account->getDisplayName()];
        $items[] = [$this->t('E-mail'), $account->getEmail()];

        // Roles
        $role_names = user_roles();
        $account_roles = array_map(
            function ($role_id) use ($role_names) {
                return $role_names[$role_id];
            },
            $account->getRoles(true)
        );
        $items[] = [
            $this->formatPlural(count($account->getRoles(true)), 'Role', 'Roles'),
            [
                '#theme' => 'item_list',
                '#items' => $account_roles,
                '#attributes' => ['class' => 'list-unstyled'],
            ],
        ];

        return [
            '#theme' => 'description_list',
            '#items' => $items,
        ];
    }

    /**
     * Return true if portlet if visible for user.
     *
     * @param AccountInterface $account
     *
     * @return boolean
     */
    public function userIsAllowed(AccountInterface $account)
    {
        return true;
    }
}
