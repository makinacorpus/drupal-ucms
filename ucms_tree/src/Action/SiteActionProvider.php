<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;

class SiteActionProvider extends AbstractActionProvider
{
    use StringTranslationTrait;

    /**
     * {inheritdoc}
     */
    public function getActions($item, bool $primaryOnly = false, array $groups = []): array
    {
        $ret = [];

        /** @var \MakinaCorpus\Ucms\Site\Site $item */
        $siteId = $item->getId();

        if ($this->isGranted(Access::OP_UPDATE, $item)) {
            $ret[] = new Action($this->t("Manage menus"), 'ucms_tree.admin.menu.list', ['site' => $siteId], 'tree', 50);
            // We do not check site state, because if user cannot view site, it
            // should not end up being checked against here (since SQL query
            // alteration will forbid it).
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item): bool
    {
        return $item instanceof Site;
    }
}
