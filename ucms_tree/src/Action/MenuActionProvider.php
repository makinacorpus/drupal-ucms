<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Umenu\Menu;

class MenuActionProvider extends AbstractActionProvider
{
    use StringTranslationTrait;

    private $siteManager;

    /**
     * {inheritdoc}
     */
    public function getActions($item, bool $primaryOnly = false, array $groups = []): array
    {
        $ret = [];

        if ($this->isGranted(Access::OP_UPDATE, $item)) {
            $ret[] = new Action($this->t("Manage links"), 'ucms_tree.admin.menu.tree', ['menu' => $item->getId()], 'th-list', -10, true, true);
            $ret[] = new Action($this->t("Edit"), 'ucms_tree.admin.menu.edit', ['menu' => $item->getId()], 'pencil', 0, true, true);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item): bool
    {
        return $item instanceof Menu;
    }
}
