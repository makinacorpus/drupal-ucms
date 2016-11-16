<?php

namespace MakinaCorpus\Ucms\Debug\Action;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProvider;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Debug\Access;

class UserActionProvider extends AbstractActionProvider
{
    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /** @var \Drupal\Core\Session\AccountInterface $item */
        if ($this->isGranted(Access::PERM_ACCESS_DEBUG)) {
            $ret[] = new Action($this->t("Debug information"), 'admin/dashboard/user/' . $item->id() . '/debug', [], 'cog', 1024, false, false, false, 'debug');
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        return $item instanceof AccountInterface;
    }
}
