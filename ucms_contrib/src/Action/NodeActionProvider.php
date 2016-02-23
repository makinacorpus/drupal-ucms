<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;

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
                $ret[] = new Action(t("Unpublish"), 'node/' . $item->id() . '/unpublish', 'dialog', 'remove-circle', 0, false, true);
            } else {
                $ret[] = new Action(t("Publish"), 'node/' . $item->id() . '/publish', 'dialog', 'ok-circle', 0, false, true);
            }
            if (_node_revision_access($item)) {
                $ret[] = new Action(t("Revisions"), 'node/' . $item->id() . '/revisions', null, 'th-list', 0, false);
            }
        }

        /*
        if (node_access('clone', $item)) {
            $ret[] = new Action(t("Clone"), 'node/' . $item->nid . '/clone', null, 'dialog', 'save', 0, false, true);
        }
        if (!empty($item->is_clonable)) {
            // ajouter au panier  permet d'ajouter le contenu au panier de l'utilisateur courant ;
            // enlever du panier  permet d'enlever le contenu du panier de l'utilisateur courant ;
        }
         */

        if (empty($item->is_flagged)) {
            $ret[] = new Action(t("Flag as inappropriate"), 'node/' . $item->id() . '/report', 'dialog', 'flag', 0, false, true);
        } else {
            $ret[] = new Action(t("Un-flag as innappropriate"), 'node/' . $item->id() . '/unreport', 'dialog', 'flag', 0, false, true);
        }
        if ($item->access('delete')) {
            $ret[] = new Action(t("Delete"), 'node/' . $item->id() . '/delete', 'dialog', 'trash', 0, false, true);
        }

        if (empty($item->is_starred)) {
            $ret[] = new Action(t("Star"), 'node/' . $item->id() . '/star', 'dialog', 'star', 0, false, true);
        } else {
            $ret[] = new Action(t("Unstar"), 'node/' . $item->id() . '/unstar', 'dialog', 'star-empty', 0, false, true);
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
