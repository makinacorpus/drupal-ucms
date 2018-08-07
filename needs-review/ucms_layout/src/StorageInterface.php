<?php

namespace MakinaCorpus\Ucms\Layout;

/**
 * Storage interface.
 */
interface StorageInterface
{
    /**
     * Load multiple layout instances
     *
     * Non existing identifiers will be excluded from the result without
     * any error or warnings.
     *
     * @param int[] $idList
     *   List of layout identifiers
     *
     * @return Layout[]
     *   Keys are layout identifiers, values are layout instances
     */
    public function loadAll($idList);

    /**
     * Save a layout
     *
     * @param Layout $layout
     */
    public function save(Layout $layout);

    /**
     * Delete a layout
     *
     * @param int|Layout $id
     *   Layout identifier or instance to delete.
     */
    public function delete($id);

    /**
     * Load a single layout instance
     *
     * @param int $id
     *
     * @return Layout
     */
    public function load($id);

    /**
     * Load a single layout instance by node identifier
     *
     * @param int $nodeId
     * @param int $siteId
     * @param boolean $createOnMiss
     *
     * @return Layout
     */
    public function findForNodeOnSite($nodeId, $siteId, $createOnMiss = false);
}
