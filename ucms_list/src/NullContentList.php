<?php

namespace MakinaCorpus\Ucms\ContentList;

use Drupal\Core\Entity\EntityInterface;

use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Site\Site;

/**
 * Null object implementation for non-existing types request at runtime
 */
class NullContentList extends AbstratContentList
{
    /**
     * {@inheritdoc}
     */
    public function fetch(EntityInterface $entity, Site $site, PageState $pageState, $options = [])
    {
        return [];
    }
}
