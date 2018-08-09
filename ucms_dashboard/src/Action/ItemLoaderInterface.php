<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Item providers serves for loading objects when calling asynchronous actions
 * derived from actions generated under a certain context where items were
 * previously loaded.
 */
interface ItemLoaderInterface
{
    /**
     * Get id from item
     *
     * @return null|ItemIdentity
     *   First value is the type string, second value the item id as string.
     *   Return null if the object is none of your concern.
     */
    public function getIdFrom($item);

    /**
     * Load item
     *
     * @return mixed
     *   An object, fully loaded, if you can handle the type, or null.
     */
    public function load(ItemIdentity $identity);
}
