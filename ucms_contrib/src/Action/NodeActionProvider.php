<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Site\Access;

class NodeActionProvider implements ActionProviderInterface
{
    /**
     * {inheritdoc}
     */
    public function getActions($item)
    {
        $ret = [];

        /* @var $item NodeInterface */

        $ret[] = new Action(t("View"), 'node/' . $item->id(), null, 'eye-open');

        if ($item->access('update')) {
            $ret[] = new Action(t("Edit"), 'node/' . $item->id() . '/edit', null, 'pencil', -100, false, true);
            if ($item->status) {
                $ret[] = new Action(t("Unpublish"), 'node/' . $item->id() . '/unpublish', 'dialog', 'remove-circle', -50, false, true);
            } else {
                $ret[] = new Action(t("Publish"), 'node/' . $item->id() . '/publish', 'dialog', 'ok-circle', -50, false, true);
            }
            if (_node_revision_access($item)) {
                $ret[] = new Action(t("Revisions"), 'node/' . $item->id() . '/revisions', null, 'th-list', -40, false);
            }
        }

        if ($item->is_global && user_access(Access::PERM_CONTENT_MANAGE_GROUP)) {
            if (empty($item->is_group)) {
                $ret[] = new Action(t("Define as group content"), 'node/' . $item->id() . '/make-group', 'dialog', 'briefcase', -30, false, true);
            } else {
                $ret[] = new Action(t("Remove from group contents"), 'node/' . $item->id() . '/unmake-group', 'dialog', 'briefcase', -30, false, true);
            }
        }

        if (empty($item->is_starred)) {
            $ret[] = new Action(t("Star"), 'node/' . $item->id() . '/star', 'dialog', 'star', -20, false, true);
        } else {
            $ret[] = new Action(t("Unstar"), 'node/' . $item->id() . '/unstar', 'dialog', 'star-empty', -20, false, true);
        }

        if (empty($item->is_flagged)) {
            $ret[] = new Action(t("Flag as inappropriate"), 'node/' . $item->id() . '/report', 'dialog', 'flag', -10, false, true);
        } else {
            $ret[] = new Action(t("Un-flag as innappropriate"), 'node/' . $item->id() . '/unreport', 'dialog', 'flag', -10, false, true);
        }

        if ($item->access('delete')) {
            $ret[] = new Action(t("Delete"), 'node/' . $item->id() . '/delete', 'dialog', 'trash', 500, false, true);
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
