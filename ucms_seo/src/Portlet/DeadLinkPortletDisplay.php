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
            $source = $item['source'];

            $rows[] = [
                check_plain($source->title),
                'node/' . $item['destination_nid'],
                [
                    '#theme' => 'ucms_dashboard_actions',
                    '#actions' => [
                        new Action(
                            "",
                            'node/' . $source->nid . '/edit',
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
                $this->t('Dead link URL'),
                $this->t('Edit'),
            ],
            '#rows'   => $rows,
            '#empty'  => $this->t('No dead link'),
        ];
    }
}
