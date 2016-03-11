<?php

namespace MakinaCorpus\Ucms\Contrib;

use MakinaCorpus\Ucms\Site\Access;

class CartStorage
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
     * Add content to favorites
     *
     * @param int $uid
     * @param int $nid
     *
     * @return boolean
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
                'nid' => $nid,
                'uid' => $uid,
            ])
            ->execute()
        ;

        return true;
    }

    /**
     * Remove content from favorites
     *
     * @param int $uid
     * @param int $nid
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
     * List favorite content
     *
     * @param int $uid
     *
     * @return int[]
     *   Ordered list of node identifiers.
     */
    public function listFor($uid)
    {
        $q = $this
            ->db
            ->select('ucms_contrib_cart', 'c')
            ->fields('c', ['nid'])
            ->condition('c.uid', $uid)
        ;

        $q->join('node', 'n', "n.nid = c.nid");

        return $q
            //->extend('PagerDefault')
            //->limit(12)
            ->addTag('node_access')
            ->addTag(Access::QUERY_TAG_CONTEXT_OPT_OUT)
            ->execute()
            ->fetchCol()
        ;
    }
}
