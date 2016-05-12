<?php

namespace MakinaCorpus\Ucms\Site\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;

class SitePortletDisplay extends AbstractDisplay
{
    use StringTranslationTrait;

    /**
     * @var string
     */
    private $emptyMessage;

    /**
     * Default constructor
     */
    public function __construct($emptyMessage = null)
    {
        $this->emptyMessage = $emptyMessage;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        $states = SiteState::getList();
        $rows   = [];

        foreach ($items as $item) {
            /* @var $item Site */
            $options = ['attributes' => ['class' => ['btn-sm']]];

            if ($item instanceof Site) {
                if ($item->state == SiteState::ON) {
                    // $this->t("Go to site")
                    $options += ['absolute' => true];
                    $action = new Action("", 'sso/goto/' . $item->id, $options, 'share-alt');
                } else {
                    // $this->t("Go to request")
                    $action = new Action("", 'admin/dashboard/site/' . $item->id, $options, 'eye-open');
                }
                $rows[] = [
                    check_plain($item->title_admin),
                    $item->ts_created->format('d/m H:i'),
                    $this->t(check_plain($states[$item->state])),
                    ['#theme' => 'ucms_dashboard_actions', '#actions' => [$action]],
                ];
            }
        }

        return [
            '#theme'  => 'table',
            '#header' => [
                $this->t('Title'),
                $this->t('Request date'),
                $this->t('Status'),
                $this->t('Link'),
            ],
            '#rows'   => $rows,
            '#empty'  => $this->emptyMessage,
        ];
    }
}
