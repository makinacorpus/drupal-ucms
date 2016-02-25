<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class NodePortletDisplay extends AbstractDisplay
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
        $rows = [];

        foreach ($items as $item) {
            /* @var $item NodeInterface */
            $rows[] = [
                check_plain($item->getTitle()),
                $item->bundle(),
                '', // @todo sites
                format_interval(time() - $item->getChangedTime()),
                $item->isPublished() ? t("published") : '',
            ];
        }

        return [
            '#theme'  => 'table',
            '#header' => [
                $this->t('Title'),
                $this->t('Type'),
                $this->t('Site(s)'),
                $this->t('Updated'),
                $this->t('Status'),
            ],
            '#rows'   => $rows,
            '#empty'  => $this->emptyMessage,
        ];
    }
}
