<?php


namespace MakinaCorpus\Ucms\Label\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Page\AbstractDisplay;


class LabelAdminDisplay extends AbstractDisplay
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
    protected function displayAs($mode, $labels)
    {
        $rows = [];

        foreach ($labels as $label) {
            $rows[] = [
                check_plain($label->name),
                ($label->is_locked == 1) ? $this->t("Non editable") : $this->t("Editable"),
                theme('udashboard_actions', ['actions' => $this->getActions($label), 'mode' => 'icon']),
            ];
        }

        return [
            '#prefix' => '<div class="col-md-12">', // FIXME should be in theme
            '#suffix' => '</div>',                  // FIXME should be in theme
            '#theme'  => 'table',
            '#header' => [$this->t("Label"), $this->t("Status"), ''],
            '#empty'  => $this->emptyMessage,
            '#rows'   => $rows,
        ];
    }
}

