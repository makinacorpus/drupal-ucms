<?php

namespace MakinaCorpus\Ucms\Contrib\Cart;

abstract class ReadonlyCartStorage implements CartStorageInterface
{
    /**
     * {@inheritdoc}
     */
    public function addFor($uid, $nid)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function has($uid, $nid)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function removeFor($uid, $nid)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function listFor($uid, $limit = 14, $offset = 0)
    {
        return [];
    }
}
