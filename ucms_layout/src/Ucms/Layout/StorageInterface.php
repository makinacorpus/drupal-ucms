<?php

namespace Ucms\Layout;

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
     * @return \Ucms\Layout\Layout[]
     *   Keys are layout identifiers, values are layout instances
     */
    public function loadAll($idList);

    /**
     * Save a layout
     *
     * @param \Ucms\Layout\Layout $layout
     */
    public function save(Layout $layout);

    /**
     * Delete a layout
     *
     * @param int|\Ucms\Layout\Layout $id
     *   Layout identifier or instance to delete.
     */
    public function delete($id);

    /**
     * Load a single layout instance
     *
     * @param int $id
     *
     * @return \Ucms\Layout\Layout
     */
    public function load($id);
}
