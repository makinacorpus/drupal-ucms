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
                    $titleSuffix = [];
                    $titleSuffix[] = '<span class="pull-right">';
                    if (!(bool)$node->is_clonable) {
                        $titleSuffix[] = '<span class="glyphicon glyphicon-lock" data-toggle="tooltip" title="' . $this->t("This content may not be cloned") . '"></span>&nbsp;';
                    }
                    if (!empty($node->origin_nid) || !empty($node->parent_nid)) {
                        $titleSuffix[] = '<span class="glyphicon glyphicon-duplicate" data-toggle="tooltip" title="' . $this->t("This content is a copy of another content") . '"></span>&nbsp;';
                    }
                    $titleSuffix[] = '</span>';

                    $titleSuffix = implode('', $titleSuffix);
                    $title = $titleSuffix . '<div class="ucms-contrib-result" data-nid="' . $node->nid . '">' . l($node->title, 'node/' . $node->nid) . '</div>';

                    // Prepares last update indication
                    $lastUpdate = ($node->getChangedTime() == 0)
                        ? $this->t("Never")
                        : format_interval(time() - $node->getChangedTime());

                    // Prepares owner name
                    $owner = isset($accountMap[$node->uid])
                        ? filter_xss(format_username($accountMap[$node->uid]))
                        : filter_xss(format_username($anonymous));

                    $rows[] = [
                        $names[$node->type],
                        $title,
                        $node->status ? $this->t("published") : $this->t("unpublished"),
                        format_interval(time() - $node->created),
                        $lastUpdate,
                        $owner,
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
                        $this->t("Created"),
                        $this->t("Last update"),
                        $this->t("Owner"),
                        '',
                    ],
                    '#rows'   => $rows,
                ];
        }
    }
}
