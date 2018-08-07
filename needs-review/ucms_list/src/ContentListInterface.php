<?php

namespace MakinaCorpus\Ucms\ContentList;

use Drupal\Core\Entity\EntityInterface;

use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Widget\WidgetInterface;

/**
 * All lists must implement this interface
 */
interface ContentListInterface extends WidgetInterface
{
    /**
     * Load nodes for display
     *
     * @param EntityInterface $entity,
     * @param Site $site
     * @param PageState $pageState
     * @param mixed[] $options
     *
     * @return int[]
     *   Sorted node indentifier list
     */
    public function fetch(EntityInterface $entity, Site $site, PageState $pageState, $options = []);
}
