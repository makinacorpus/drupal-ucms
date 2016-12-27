<?php

namespace MakinaCorpus\Ucms\Contrib\Page;

use MakinaCorpus\Ucms\Dashboard\Page\DefaultNodePageType;
use MakinaCorpus\Ucms\Dashboard\Page\PageBuilder;

use Symfony\Component\HttpFoundation\Request;

class MyNodePageType extends DefaultNodePageType
{
    /**
     * {@inheritdoc}
     */
    public function build(PageBuilder $builder, Request $request)
    {
        parent::build($builder, $request);

        $builder
            ->addBaseQueryParameter('user_id', $request->get('user_id'))
        ;
    }
}
