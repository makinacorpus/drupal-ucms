<?php


namespace MakinaCorpus\Ucms\User\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;


class UserActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;


    /**
     * Default constructor
     */
    public function __construct() {}


    /**
     * {@inheritdoc}
     */
    public function getActions($item)
    {
        $actions = [];

        $actions[] = new Action($this->t("View"), 'admin/dashboard/user/' . $item->uid, null, 'eye-open', 1, true, true);

        $status_action  = ($item->status == 0) ? $this->t("Enable") : $this->t("Disable");
        $status_icon    = ($item->status == 0) ? 'ok-circle' : 'ban-circle';
        $actions[] = new Action($status_action, 'admin/dashboard/user/' . $item->uid . '/toggle', 'dialog', $status_icon, 2, true, true);

        $actions[] = new Action($this->t("Edit"), 'admin/dashboard/user/' . $item->uid . '/edit', null, 'pencil', 3, false, true);
        $actions[] = new Action($this->t("Change email"), 'admin/dashboard/user/' . $item->uid . '/change-email', 'dialog', 'pencil', 4, false, true);
        $actions[] = new Action($this->t("Reset password"), 'admin/dashboard/user/' . $item->uid . '/reset-password', 'dialog', 'refresh', 5, false, true);
        $actions[] = new Action($this->t("Delete"), 'admin/dashboard/user/' . $item->uid . '/delete', 'dialog', 'trash', 6, false, true);

        return $actions;
    }


    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return is_object($item) && property_exists($item, 'uid') && property_exists($item, 'mail');
    }
}
