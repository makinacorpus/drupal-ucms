<?php
/**
 * @file
 * CRUD related function, so they won't pollute the module file.
 */

/**
 * Load multiple layout instances.
 *
 * @param int[] $idList
 *   List of layout identifiers.
 *
 * @return \Ucms\Layout\Layout[]
 */
function ucms_layout_load_multiple($idList) {
  return ucms_layout_context_get()->getStorage()->loadAll($idList);
}

/**
 * Save a layout.
 *
 * @param \Ucms\Layout\Layout $layout
 *   Layout to save.
 */
function ucms_layout_save(\Ucms\Layout\Layout $layout) {
  return ucms_layout_context_get()->getStorage()->save($layout);
}

/**
 * Delete a layout.
 *
 * @param int|\Ucms\Layout\Layout $id
 *   Layout identifier or instance to delete.
 */
function ucms_layout_delete($id) {
  return ucms_layout_context_get()->getStorage()->delete($id);
}

/**
 * Load a layout instances.
 *
 * @param int[] $idList
 *   List of layout identifiers.
 *
 * @return \Ucms\Layout\Layout[]
 */
function ucms_layout_load($id) {
  return ucms_layout_context_get()->getStorage()->load($id);
}
