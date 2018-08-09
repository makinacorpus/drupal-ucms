<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;

class SiteActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item): array
    {
        $ret = [];

        if ($item instanceof Site) {
            $ret[] = $this
                ->create('site.menus', new TranslatableMarkup("Manage menus"), 'tree', 50)
                ->group('tree')
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_UPDATE, $item);
                })
                ->asLink('ucms_tree.admin.menu.list', ['site' => $item->getId()])
            ;
        }

        return $ret;
    }
}
