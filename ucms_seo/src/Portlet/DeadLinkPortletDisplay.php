<?php


namespace MakinaCorpus\Ucms\Seo\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;


class DeadLinkPortletDisplay extends AbstractDisplay
{
    use StringTranslationTrait;

    protected function displayAs($mode, $items)
    {
        $rows   = [];

        foreach ($items as $item) {
            $node = $item['source'];

            $rows[] = [
                $node ? check_plain($node->title): '',
                check_plain($item['source_field']),
                $item['destination_url'],
                $item['destination_deleted'] ? $this->t('Deleted') : $this->t('Unpublished'),
                [
                    '#theme' => 'ucms_dashboard_actions',
                    '#actions' => [
                        new Action(
                            "",
                            'node/' . $node->nid . '/edit',
                            ['attributes' => ['class' => ['btn-sm']]],
                            'share-alt'
                        )
                    ]
                ],
            ];
        }

        return [
            '#theme'  => 'table',
            '#header' => [
                $this->t('Title'),
                $this->t('Field'),
                $this->t('Dead link URL'),
                $this->t('Reason'),
                $this->t('Edit'),
            ],
            '#rows'   => $rows,
            '#empty'  => $this->t('No dead link'),
        ];
    }
}
