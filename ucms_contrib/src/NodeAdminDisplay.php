<?php

namespace MakinaCorpus\Ucms\Contrib;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class NodeAdminDisplay extends AbstractDisplay
{
    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    protected function getSupportedModes()
    {
        return [
            'table' => $this->t("table"),
            'grid'  => $this->t("thumbnail grid"),
            'list'  => $this->t("teaser list"),
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
                    '#mode'  => $mode,
                ];

            case 'list':
                return [
                    '#theme' => 'ucms_contrib_content_result_grid',
                    '#nodes' => $nodes,
                    '#mode' => $mode,
                    '#view_mode' => 'teaser',
                ];

            case 'table':
                $rows = [];
                $names = node_type_get_names();

                $accountMap = [];
                foreach ($nodes as $node) {
                    $accountMap[$node->uid] = $node->uid;
                }
                $accountMap = user_load_multiple($accountMap);
                $anonymous = drupal_anonymous_user();

                foreach ($nodes as $node) {
                    // FIXME should be in theme
                    $title = '<div class="ucms-contrib-result" data-nid="'.$node->nid.'">'.
                        (!(bool)$node->is_clonable ? '<span class="glyphicon glyphicon-lock"></span>&nbsp;' : '').
                        l(
                            $node->title,
                            'node/'.$node->nid
                        ).'</div>';
                    $rows[] = [
                        $names[$node->type],
                        $title,
                        $node->status ? $this->t("published") : $this->t("unpublished"),
                        format_date($node->created),
                        isset($accountMap[$node->uid])
                            ? filter_xss(format_username($accountMap[$node->uid]))
                            : filter_xss(format_username($anonymous)),
                        theme('ucms_dashboard_actions', ['actions' => $this->getActions($node), 'mode' => 'icon']),
                    ];
                }

                return [
                    '#prefix' => '<div class="col-md-12" data-mode="table">', // FIXME should be in theme
                    '#suffix' => '</div>', // FIXME should be in theme
                    '#theme'  => 'table',
                    '#header' => [
                        $this->t("Type"),
                        $this->t("Title"),
                        $this->t("Status"),
                        $this->t("Date"),
                        $this->t("Owner"),
                        '',
                    ],
                    '#rows'   => $rows,
                ];
        }
    }
}
