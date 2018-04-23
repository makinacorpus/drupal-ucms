<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\Site;

class SiteActionProvider implements ActionProviderInterface
{
    use StringTranslationTrait;

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * @var AccountInterface
     */
    private $currentUser;

    /**
     * Default constructor
     *
     * @param GroupManager $manager
     */
    public function __construct(GroupManager $groupManager, AccountInterface $currentUser)
    {
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;
    }

    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        /** @var \MakinaCorpus\Ucms\Site\Site $item */
        $ret = [];

        if ($this->groupManager->userCanManageAll($this->currentUser)) {
            $ret[] = new Action($this->t("Attach to group"), 'admin/dashboard/site/' . $item->getId() . '/group-attach', 'dialog', 'tent', 200, false, true, false, 'group');
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
