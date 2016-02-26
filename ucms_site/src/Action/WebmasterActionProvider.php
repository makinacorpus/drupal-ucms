<?php


namespace MakinaCorpus\Ucms\Site\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;


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

        if ($item->getUserId() != $this->currentUser->id()) {
            if ((int) $item->getRole() === Access::ROLE_WEBMASTER) {
                $path = $this->buildWebmasterUri($item, 'demote');
                $actions[] = new Action($this->t("Demote as contributor"), $path, 'dialog', 'circle-arrow-down', 10, true, true);
            } else {
                $path = $this->buildWebmasterUri($item, 'promote');
                $actions[] = new Action($this->t("Promote as webmaster"), $path, 'dialog', 'circle-arrow-up', 10, true, true);
            }

            $path = $this->buildWebmasterUri($item, 'delete');
            $actions[] = new Action($this->t("Delete from webmasters"), $path, 'dialog', 'remove', 20, true, true);
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

