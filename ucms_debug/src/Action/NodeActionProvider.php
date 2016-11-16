<?php

namespace MakinaCorpus\Ucms\Debug\Action;

use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Debug\Access;

class NodeActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /** @var \Drupal\node\NodeInterface $item */
        if ($this->isGranted(Access::PERM_ACCESS_DEBUG) && $this->isGranted(Permission::VIEW, $item)) {
            $ret[] = new Action($this->t("Debug information"), 'node/' . $item->id() . '/debug', [], 'cog', 1024, false, false, false, 'debug');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof NodeInterface;
    }
}
