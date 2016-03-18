<?php


namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\UserAccess;


class WebmasterActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;


    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var AccountInterface
     */
    private $currentUser;


    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager, AccountInterface $currentUser)
    {
        $this->manager = $manager;
        $this->currentUser = $currentUser;
    }


    /**
     * {@inheritdoc}
     * @param SiteAccessRecord $item
     */
    public function getActions($item)
    {
        $actions = [];

        /* @var \MakinaCorpus\Ucms\Site\Site */
        $site = ucms_site_manager()->getStorage()->findOne($item->getSiteId());

        if (user_access(UserAccess::PERM_MANAGE_ALL) || ($item->getUserId() != $this->currentUser->id() && $item->getUserId() != $site->uid)) {
            $actions[] = new Action($this->t("View"), 'admin/dashboard/user/' . $item->getUserId(), null, 'eye-open', 0, true, true);

            if ((int) $item->getRole() === Access::ROLE_WEBMASTER) {
                $path = $this->buildWebmasterUri($item, 'demote');
                $actions[] = new Action($this->t("Demote as contributor"), $path, 'dialog', 'circle-arrow-down', 10, true, true);
            } else {
                $path = $this->buildWebmasterUri($item, 'promote');
                $actions[] = new Action($this->t("Promote as webmaster"), $path, 'dialog', 'circle-arrow-up', 10, true, true);
            }

            $actions[] = new Action($this->t("Edit"), 'admin/dashboard/user/' . $item->getUserId() . '/edit', null, 'pencil', 10, false, true);
            $actions[] = new Action($this->t("Change email"), 'admin/dashboard/user/' . $item->getUserId() . '/change-email', 'dialog', 'pencil', 20, false, true);
            $actions[] = new Action($this->t("Reset password"), 'admin/dashboard/user/' . $item->getUserId() . '/reset-password', 'dialog', 'refresh', 30, false, true);

            $path = $this->buildWebmasterUri($item, 'delete');
            $actions[] = new Action($this->t("Delete from this site"), $path, 'dialog', 'remove', 40, false, true);
        }

        return $actions;
    }


    /**
     * {@inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof SiteAccessRecord;
    }


    /**
     * Builds the URI for a given operation on site accesses.
     *
     * @param SiteAccessRecord $item
     * @param string $op
     * @return string
     */
    protected function buildWebmasterUri($item, $op)
    {
        return 'admin/dashboard/site/' . $item->getSiteId()
            . '/webmaster/' . $item->getUserId() . '/' . $op;
    }
}

