<?php

namespace MakinaCorpus\Ucms\Tree\Action;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Umenu\Menu;

class MenuActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item): array
    {
        $ret = [];

        if ($item instanceof Menu) {

            $ret[] = $this
                ->create('site_menu.links', new TranslatableMarkup("Manage links"), 'th-list', -10)
                ->redirectHere()
                ->primary()
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_UPDATE, $item);
                })
                ->asLink('ucms_tree.admin.menu.tree', ['menu' => $item->getId()])
            ;

            $ret[] = $this
                ->create('site_menu.edit', new TranslatableMarkup("Edit"), 'pencil')
                ->redirectHere()
                ->isGranted(function () use ($item) {
                    return $this->isGranted(Access::OP_UPDATE, $item);
                })
                ->asLink('ucms_tree.admin.menu.edit', ['menu' => $item->getId()])
            ;
        }

        return $ret;
    }
}
