<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Drupal\Dashboard\Page\PageBuilder;

use Symfony\Component\HttpFoundation\Request;

/**
 * Default node admin page implementation, suitable for most use cases
 */
class MineNodeAdminPage extends DefaultNodeAdminPage
{
    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        parent::build($builder, $request);

        // @todo filter user
    }
}
