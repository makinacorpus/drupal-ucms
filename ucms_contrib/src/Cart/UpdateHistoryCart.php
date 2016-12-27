<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

use MakinaCorpus\Ucms\Site\Access;

class UpdateHistoryCart extends ReadonlyCartStorage
{
    private $database;

    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    public function listFor($uid, $limit = 14, $offset = 0)
    {
        $query = $this->database->select('node', 'n');

        $query->fields('n', ['nid']);
        $query->addExpression($uid, 'uid');
        $query->addField('n', 'changed', 'added');

        return $query
            ->condition('n.uid', $uid)
            ->orderBy('n.changed', 'desc')
            ->orderBy('n.created', 'desc')
            ->addTag('node_access')
            ->addTag(Access::QUERY_TAG_CONTEXT_OPT_OUT)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, CartItem::class)
        ;
    }
}
