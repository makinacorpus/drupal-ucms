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
        foreach ($ret['nodes'] as $nid => $content) {
            $ret['nodes'][$nid] = [
                '#prefix' => '<div class="ucms-cart-item col-md-6" data-nid="' . $nid . '">',
                '#suffix' => '</div>',
                'content' => $content,
            ];
        }
        return $ret;
    }
}
