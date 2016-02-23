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
            new Action('Edit my information', 'admin/dashboard/user/my-account', 'dialog', 'edit'),
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

        // Prevent any modification of the global object
        $items[] = [$this->t('Username'), $account->getDisplayName()];
        $items[] = [$this->t('E-mail'), $account->getEmail()];

        $roles = $account->getRoles(true);
        $items[] = [
            $this->formatPlural(count($roles), 'Role', 'Roles'),
            [
                '#theme'      => 'item_list',
                '#items'      => $roles,
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
        // @TODO should be in a constructor
        $this->account = $account;

        return true;
    }
}
