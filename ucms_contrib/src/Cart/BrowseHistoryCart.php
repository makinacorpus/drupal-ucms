<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

use MakinaCorpus\Ucms\Site\Access;

class BrowseHistoryCart extends ReadonlyCartStorage
{
    private $database;

    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    public function listFor($uid, $limit = 14, $offset = 0)
    {
        $query = $this->database->select('history', 'h');

        $query->join('node', 'n', "h.nid = n.nid");
        $query->fields('h', ['nid', 'uid']);
        $query->addField('h', 'timestamp', 'added');

        return $query
            ->condition('h.uid', $uid)
            ->orderBy('h.timestamp', 'desc')
            ->range($offset, $limit)
            ->addTag('node_access')
            ->addTag(Access::QUERY_TAG_CONTEXT_OPT_OUT)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, CartItem::class)
        ;
    }
}
