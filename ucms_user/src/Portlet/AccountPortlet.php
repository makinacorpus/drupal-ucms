<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Drupal\Calista\Portlet\AbstractPortlet;

class AccountPortlet extends AbstractPortlet
{
    private $account;

    /**
     * Default constructor
     *
     * @param AccountInterface $account
     */
    public function __construct(AccountInterface $account)
    {
        $this->account = $account;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->t("My account");
    }

    /**
     * {@inheritdoc}
     */
    public function getPath()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getActions()
    {
        return [
            new Action($this->t('Edit my information'), 'admin/dashboard/user/my-account', 'dialog', 'edit'),
            new Action($this->t('Edit my password'), 'admin/dashboard/user/my-password', 'dialog', 'edit'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        $items    = [];
        $account  = $this->account;

        $items[] = [$this->t('Username'), $account->getDisplayName()];
        $items[] = [$this->t('E-mail'), check_plain($account->getEmail())];

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

        foreach ($items as $index => $item) {
            $items[$index] = [
                'title' => ['#markup' => '<strong>' . $item[0] . '</strong>&nbsp;: '],
                'content' => is_array($item[1]) ? $item[1] : ['#markup' => $item[1]],
                'sep' => ['#markup' => '<br/>']
            ];
        }

        return drupal_render($items);
    }
}
