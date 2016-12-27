<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

use MakinaCorpus\Ucms\Site\Access;

/**
 * Default cart implementation using database
 */
final class CartStorage implements CartStorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function addFor($uid, $nid)
    {
        $exists = (bool)$this
            ->db
            ->query("SELECT 1 FROM {ucms_contrib_cart} WHERE nid = :nid AND uid = :uid", [
                ':nid' => $nid,
                ':uid' => $uid,
            ])
            ->fetchField()
        ;

        if ($exists) {
            return false;
        }

        $this
            ->db
            ->merge('ucms_contrib_cart')
            ->key([
                'nid'       => $nid,
                'uid'       => $uid,
                'ts_added'  => (new \DateTime())->format('Y-m-d H:i:s'),
            ])
            ->execute()
        ;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function has($uid, $nid)
    {
        return (bool)$this
            ->db
            ->query(
                "SELECT 1 FROM {ucms_contrib_cart} WHERE nid = ? AND uid = ?",
                [$nid, $uid]
            )
            ->fetchField()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFor($uid, $nid)
    {
        $this
            ->db
            ->delete('ucms_contrib_cart')
            ->condition('nid', $nid)
            ->condition('uid', $uid)
            ->execute()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function listFor($uid)
    {
        $q = $this
            ->db
            ->select('ucms_contrib_cart', 'c')
            ->condition('c.uid', $uid)
        ;

        $q->addField('c', 'nid');
        $q->addField('c', 'uid');
        $q->addField('c', 'ts_added', 'added');
        $q->join('node', 'n', "n.nid = c.nid");

        return $q
            //->extend('PagerDefault')
            //->limit(12)
            // @todo Restore me!
            //->addTag('node_access')
            ->orderBy('c.ts_added', 'desc')
            ->addTag(Access::QUERY_TAG_CONTEXT_OPT_OUT)
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, CartItem::class)
        ;
    }
}
