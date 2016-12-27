<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

interface CartStorageInterface
{
    /**
     * Is this storage readonly
     *
     * @return boolean
     */
    public function isReadonly();

    /**
     * Add content to favorites
     *
     * @param int $uid
     * @param int $nid
     *
     * @return boolean
     */
    public function addFor($uid, $nid);

    /**
     * Current user has item?
     *
     * @param int $uid
     * @param int $nid
     *
     * @return boolean
     */
    public function has($uid, $nid);

    /**
     * Remove content from favorites
     *
     * @param int $uid
     * @param int $nid
     */
    public function removeFor($uid, $nid);

    /**
     * List favorite content
     *
     * @param int $uid
     * @param int $limit
     * @param int $offset
     *
     * @return CartItem[]
     *   Ordered list of items.
     */
    public function listFor($uid, $limit = 14, $offset = 0);
}
