<?php

namespace MakinaCorpus\Ucms\Contrib;

class ContentAdminListDisplay extends AbstractListDisplay
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'contentadmin';
    }

    /**
     * {@inheritdoc}
     */
    protected function getSupportedModes()
    {
        return [
            'grid'  => t("thumbnail grid"),
            'list'  => t("teaser list"),
            'table' => t("table"),
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
                    ];
                }

                return [
                    '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
                    '#suffix' => '</div>', // FIXME should be in theme
                    '#theme'  => 'table',
                    '#header' => [t("Type"), t("Title"), t("Status"), t("Date"), t("Owner")],
                    '#rows'   => $rows,
                ];
        }
    }
}
