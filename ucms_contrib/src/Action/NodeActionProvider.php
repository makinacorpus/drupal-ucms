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

        $ret[] = new Action(t("View"), 'node/' . $item->nid);
        if (node_access('update', $item)) {
            $ret[] = new Action(t("Edit"), 'node/' . $item->nid . '/edit');
            if ($item->status) {
                $ret[] = new Action(t("Publish"), 'node/' . $item->nid . '/publish', 'dialog');
            } else {
                $ret[] = new Action(t("Unpublish"), 'node/' . $item->nid . '/unpublish', 'dialog');
            }
            if (_node_revision_access($item)) {
                $ret[] = new Action(t("Revisions"), 'node/' . $item->nid . '/revisions');
            }
        }
        if (node_access('clone', $item)) {
            $ret[] = new Action(t("Clone"), 'node/' . $item->nid . '/clone', 'dialog');
        }
        if (!empty($item->is_clonable)) {
            // ajouter au panier  permet d'ajouter le contenu au panier de l'utilisateur courant ;
            // enlever du panier  permet d'enlever le contenu du panier de l'utilisateur courant ;
        }
        if (empty($item->is_flagged)) {
            $ret[] = new Action(t("Flag as inappropriate"), 'node/' . $item->nid . '/report', 'dialog');
        } else {
            $ret[] = new Action(t("Un-flag as innappropriate"), 'node/' . $item->nid . '/unreport', 'dialog');
        }
        if (node_access('delete', $item)) {
            $ret[] = new Action(t("Delete"), 'node/' . $item->nid . '/delete');
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
