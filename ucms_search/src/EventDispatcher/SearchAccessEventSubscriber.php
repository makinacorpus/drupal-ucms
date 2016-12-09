<?php

namespace MakinaCorpus\Ucms\Search\EventDispatcher;

use Drupal\Core\Entity\EntityManager;

use MakinaCorpus\Ucms\Search\IndexStorage;
use MakinaCorpus\Ucms\Search\Lucene\Query;
use MakinaCorpus\Ucms\Search\Search;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class SearchAccessEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var IndexStorage
     */
    private $storage;

    /**
     * Constructor.
     *
     * @param IndexStorage $storage
     * @param EntityManager $entityManager
     */
    public function __construct(IndexStorage $storage, EntityManager $entityManager)
    {
        $this->storage = $storage;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'ucms_search.search_create' => [
                ['onUcmsSearchsearchCreate', 0],
            ],
        ];
    }

    public function onUcmsSearchsearchCreate(GenericEvent $event)
    {
        $search = $event->getSubject();

        if (!$search instanceof Search) {
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
