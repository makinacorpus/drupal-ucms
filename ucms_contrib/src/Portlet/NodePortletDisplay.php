<?php

namespace MakinaCorpus\Ucms\Contrib\Portlet;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Dashboard\Page\AbstractDisplay;

class NodePortletDisplay extends AbstractDisplay
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
    protected function displayAs($mode, $items)
    {
        $rows = [];

        foreach ($items as $item) {
            /** @var $item NodeInterface */
            $lastUpdate = ($item->getChangedTime() == 0)
                ? $this->t("Never")
                : format_interval(time() - $item->getChangedTime());

            $rows[] = [
                check_plain($item->getTitle()),
                $item->bundle(),
                '', // @todo sites
                $lastUpdate,
                $item->isPublished() ? $this->t("published") : '',
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
