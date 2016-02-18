<?php

namespace MakinaCorpus\Ucms\User\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Portlet\Portlet;

/**
 * Class AccountPortlet
 * @package MakinaCorpus\Ucms\User\Dashboard
 */
class AccountPortlet extends Portlet
{
    use StringTranslationTrait;

    /**
     * @var \stdClass
     */
    private $account;

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
        return [];
    }

    /**
     * Return the render array for this portlet.
     * @return array
     */
    public function getContent()
    {
        // Prevent any modification of the global object
        $account = user_load($GLOBALS['user']->uid);

        $items[] = [
            $this->t('Username'),
            check_plain(format_username($account)),
        ];

        $items[] = [
            $this->t('E-mail'),
            $account->mail,
        ];

        $items[] = [
            $this->formatPlural(count($account->roles), 'Role', 'Roles'),
            [
                '#theme' => 'item_list',
                '#items' => $account->roles,
                '#attributes' => ['class' => 'list-unstyled'],
            ],
        ];

        // TODO
        $sites = ['@todo'];
        $items[] = [
            $this->formatPlural(count($sites), 'Site', 'Sites'),
            [
                '#theme' => 'item_list',
                '#items' => $sites,
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
     * @param \stdClass $account
     * @return bool
     */
    public function userIsAllowed(\stdClass $account)
    {
        $this->account = $account;
        return true;
    }
}
