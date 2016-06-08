<?php

namespace MakinaCorpus\Ucms\Contrib;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class NodeCartDisplay extends AbstractDisplay
{
    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $nodes)
    {
        if (empty($nodes)) {
            return [];
        }
        $ret = node_view_multiple($nodes, UCMS_VIEW_MODE_FAVORITE);
        foreach ($nodes as $nid => $node) {
            $ret['nodes'][$nid] = [
                '#prefix' => '<div class="ucms-cart-item" draggable="true" data-nid="'.$nid.'" data-bundle="'.$node->getType().'">',
                '#suffix' => '</div>',
                'content' => $ret['nodes'][$nid],
            ];
        }
        return $ret;
    }
}
