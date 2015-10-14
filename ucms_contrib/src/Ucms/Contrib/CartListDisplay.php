<?php

namespace Ucms\Contrib;

class CartListDisplay extends AbstractListDisplay
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'favorite';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedModes()
    {
        return [
            'grid'  => t("thumbnail grid"),
            'list'  => t("teaser list"),
            'title' => t("titles"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $nodes)
    {
        switch ($mode) {

            case 'grid':
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

            case 'list':
                // @todo
            case 'title':
                $items = [];

                foreach ($nodes as $nid => $node) {
                    $items[] = '<div class="ucms-cart-item" data-nid="' . $nid . '">' . l($node->title, 'node/' . $node->nid) . '</div>';
                }

                return [
                    '#theme'      => 'item_list__ucms_contrib_cart',
                    '#items'      => $items,
                    '#attributes' => ['class' => ['col-md-12']],
                ];
        }
    }
}
