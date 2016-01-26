<?php

namespace MakinaCorpus\Ucms\Contrib\Action;

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

        $ret[] = new Action(t("View"), 'node/' . $item->nid, null, 'eye-open');
        if (node_access('update', $item)) {
            $ret[] = new Action(t("Edit"), 'node/' . $item->nid . '/edit', null, 'pencil', 0, true, true);
            if ($item->status) {
                $ret[] = new Action(t("Unpublish"), 'node/' . $item->nid . '/unpublish', 'dialog', 'remove-circle', 0, false, true);
            } else {
                $ret[] = new Action(t("Publish"), 'node/' . $item->nid . '/publish', 'dialog', 'ok-circle', 0, false, true);
            }
            if (_node_revision_access($item)) {
                $ret[] = new Action(t("Revisions"), 'node/' . $item->nid . '/revisions', null, 'th-list', 0, false);
            }
        }
        if (node_access('clone', $item)) {
            $ret[] = new Action(t("Clone"), 'node/' . $item->nid . '/clone', null, 'dialog', 'save', 0, false, true);
        }
        if (!empty($item->is_clonable)) {
            // ajouter au panier  permet d'ajouter le contenu au panier de l'utilisateur courant ;
            // enlever du panier  permet d'enlever le contenu du panier de l'utilisateur courant ;
        }
        if (empty($item->is_flagged)) {
            $ret[] = new Action(t("Flag as inappropriate"), 'node/' . $item->nid . '/report', 'dialog', 'flag', 0, false, true);
        } else {
            $ret[] = new Action(t("Un-flag as innappropriate"), 'node/' . $item->nid . '/unreport', 'dialog', 'flag', 0, false, true);
        }
        if (node_access('delete', $item)) {
            $ret[] = new Action(t("Delete"), 'node/' . $item->nid . '/delete', 'dialog', 'trash', 0, false, true);
        }

        return $ret;
    }

    /**
     * {inheritdoc}
     */
    public function supports($item)
    {
        // That should be enough
        return is_object($item) && property_exists($item, 'nid') && property_exists($item, 'is_global');
    }
}
