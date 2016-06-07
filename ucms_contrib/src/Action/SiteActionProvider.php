<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

class SiteActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    private $manager;
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
     * {inheritdoc}
     */
    public function getActions($item)
    {
        /** @var $item Site */
        $ret = [];

        $account  = $this->currentUser;
        $access   = $this->manager->getAccess();

        if ($access->userCanOverview($account, $item)) {
            $ret[] = new Action($this->t("View content"), 'admin/dashboard/content/site/' . $item->getId(), null, 'file', 100, false, false, false, 'content');
            $ret[] = new Action($this->t("View medias"), 'admin/dashboard/media/site/' . $item->getId(), null, 'picture', 100, false, false, false, 'content');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof Site;
    }
}
