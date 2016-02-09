<?php


namespace MakinaCorpus\Ucms\Label\Page;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;


class LabelAdminDisplay extends AbstractDisplay
{
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
    protected function displayAs($mode, $labels)
    {
        $rows = [];

        foreach ($labels as $label) {
            $rows[] = [
                check_plain($label->name),
                ($label->is_locked == 1) ? t("Non editable") : t("Editable"),
                theme('ucms_dashboard_actions', ['actions' => $this->getActions($label), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [t("Label"), t("Status"), ''],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }

}

