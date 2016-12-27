<?php

namespace MakinaCorpus\Ucms\Contrib;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class NodeCartDisplay extends AbstractDisplay
{
    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $ret = [];
        if (empty($items)) {
            return $ret;
        }
        /** @var \MakinaCorpus\Ucms\Contrib\Cart\CartItem $item */
        foreach ($items as $item) {
            $nid = $item->getNodeId();
            $node = $item->getNode();
            $ret['nodes'][$nid] = [
                '#prefix' => '<div class="ucms-cart-item" draggable="true" data-nid="'.$nid.'" data-bundle="'.$node->getType().'">',
                '#suffix' => '</div>',
                'content' => node_view($node, UCMS_VIEW_MODE_FAVORITE),
            ];
        }
        return $ret;
    }
}
