<?php

namespace MakinaCorpus\Ucms\Contrib;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class NodeAdminDisplay extends AbstractDisplay
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedModes()
    {
        return [
            'table' => t("table"),
            'grid'  => t("thumbnail grid"),
            'list'  => t("teaser list"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $nodes)
    {
        switch ($mode) {

            case 'grid':
                return [
                    '#theme' => 'ucms_contrib_content_result_grid',
                    '#nodes' => $nodes,
                ];

            case 'list':
                return [
                    '#theme'      => 'ucms_contrib_content_result_grid',
                    '#nodes'      => $nodes,
                    '#view_mode'  => 'teaser',
                ];

            case 'table':
                $rows   = [];
                $names  = node_type_get_names();

                $accountMap = [];
                foreach ($nodes as $node) {
                    $accountMap[$node->uid] = $node->uid;
                }
                $accountMap = user_load_multiple($accountMap);
                $anonymous = drupal_anonymous_user();

                foreach ($nodes as $node) {
                    $rows[] = [
                        $names[$node->type],
                        '<div class="ucms-contrib-result" data-nid="' . $node->nid . '">' . l($node->title, 'node/' . $node->nid) . '</div>',
                        $node->status ? t("published") : t("unpublished"),
                        format_date($node->created),
                        isset($accountMap[$node->uid]) ? format_username($accountMap[$node->uid]) : format_username($anonymous),
                        theme('ucms_dashboard_actions', ['actions' => $this->getActions($node), 'mode' => 'icon']),
                    ];
                }

                return [
                    '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
                    '#suffix' => '</div>', // FIXME should be in theme
                    '#theme'  => 'table',
                    '#header' => [t("Type"), t("Title"), t("Status"), t("Date"), t("Owner"), ''],
                    '#rows'   => $rows,
                ];
        }
    }
}
