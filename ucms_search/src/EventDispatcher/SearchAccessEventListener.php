<?php

namespace MakinaCorpus\Ucms\Search\EventDispatcher;

use MakinaCorpus\Ucms\Search\Lucene\Query;
use MakinaCorpus\Ucms\Search\Lucene\TermCollectionQuery;
use MakinaCorpus\Ucms\Search\QueryAlteredSearch;

use Symfony\Component\EventDispatcher\GenericEvent;

class SearchAccessEventListener
{
    public function onUcmsSearchsearchCreate(GenericEvent $event)
    {
        $search = $event->getSubject();

        if (!$search instanceof QueryAlteredSearch) {
            return;
        }

        // Get current user grant and go for it
        //   @todo - fix node_access_grants() call to some service?
        $grants = node_access_grants('view');

        /* @var $filter TermCollectionQuery */
        $filter = $search
            ->getFilterQuery()
            ->createTermCollection(Query::OP_OR)
            ->setField('node_access')
        ;

        if (empty($grants)) {
            $filter->add('all:0'); // No grants means anymous
        } else {
            foreach ($grants as $realm => $gids) {
                foreach ($gids as $gid) {
                    $filter->add($realm . ':' . $gid);
                }
            }
        }
    }
}
