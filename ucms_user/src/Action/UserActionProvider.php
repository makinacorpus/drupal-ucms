<?php


namespace MakinaCorpus\Ucms\User\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;


class UserActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;


    /**
     * @var AccountInterface
     */
    private $currentUser;


    /**
     * Default constructor
     *
     * @param AccountInterface $currentUser
     */
    public function __construct(AccountInterface $currentUser)
    {
        $this->currentUser = $currentUser;
    }


    /**
     * {@inheritdoc}
     */
    public function getActions($item)
    {
        $actions = [];

        $actions[] = new Action($this->t("View"), 'admin/dashboard/user/' . $item->uid, null, 'eye-open', 1, true, true);

        if ($item->status == 0) {
            $action_title = $this->t("Enable");
            $action_path  = 'admin/dashboard/user/' . $item->uid . '/enable';
            $action_icon  = 'ok-circle';
        } else {
            $action_title = $this->t("Disable");
            $action_path  = 'admin/dashboard/user/' . $item->uid . '/disable';
            $action_icon  = 'ban-circle';
        }

        $action_disabled  = ($item->uid === $this->currentUser->id());
        $actions[] = new Action($action_title, $action_path, 'dialog', $action_icon, 2, true, true, $action_disabled);

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
