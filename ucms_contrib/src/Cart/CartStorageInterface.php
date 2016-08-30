<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

interface CartStorageInterface
{
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
     *
     * @return int[]
     *   Ordered list of node identifiers.
     */
    public function listFor($uid);
}
