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

  $ret = [];

  $cidList = array_map(function ($value) {
    return 'layout:' . $value;
  }, $idList);

  foreach (cache_get_multiple($cidList, 'cache_layout') as $layout) {
    if ($layout instanceof \Ucms\Layout\Layout) {
      $ret[$layout->getId()] = $layout;
    }
  }

  $missing = array_diff($idList, array_keys($ret));
  if (!empty($missing)) {

    $rows = db_select('ucms_layout', 'l')
      ->fields('l')
      ->condition('l.id', $missing)
      ->execute()
      ->fetchAllAssoc('id')
    ;

    $data = db_select('ucms_layout_data', 'd')
      ->fields('d')
      ->condition('d.layout_id', $missing)
      ->orderBy('d.layout_id')
      ->orderBy('d.region')
      ->orderBy('d.weight')
      ->execute()
    ;

    // Build a structure map for easier and quicker lookup then.
    $regionMap = [];
    foreach ($data as $line) {
      $regionMap[$line->layout_id][$line->region][] = new \Ucms\Layout\Item($line->nid, $line->view_mode);
    }

    foreach ($rows as $row) {

      $layout = (new \Ucms\Layout\Layout())
        ->setId($row->id)
        ->setSiteId($row->site_id)
        ->setAccountId($row->uid)
        ->setAdminTitle($row->title_admin)
        ->setTitle($row->title)
      ;

      if (!empty($regionMap[$row->id])) {
        foreach ($regionMap[$row->id] as $name => $items) {
          $region = $layout->getRegion($name);
          foreach ($items as $item) {
            $region->append($item);
          }
          $region->toggleUpdateStatus(false);
        }
      }

      cache_set('layout:' . $layout->getId(), $layout, 'cache_layout');

      $ret[$row->id] = $layout;
    }
  }

  return $ret;
}

/**
 * Update a single region of a layout.
 *
 * You should never call this method directly.
 *
 * @param \Ucms\Layout\Layout $layout
 *   Layout to save.
 * @param string $region
 *   Set this to update only one region.
 *
 * @return boolean
 *   True if the instance was saved, false if not modified.
 */
function _ucms_layout_region_update(\Ucms\Layout\Layout $layout, \Ucms\Layout\Region $region) {
  if ($region->isUpdated()) {

    db_delete('ucms_layout_data')
      ->condition('layout_id', $layout->getId())
      ->condition('region', $region->getName())
      ->execute()
    ;

    $values = [];

    /* @var $item \Ucms\Layout\Item */
    foreach ($region as $delta => $item) {
      $values[] = [
        $layout->getId(),
        $region->getName(),
        $item->getNodeId(),
        $delta,
        $item->getViewMode(),
      ];
    }

    if (!empty($values)) {
      $q = db_insert('ucms_layout_data');
      $q->fields(['layout_id', 'region', 'nid', 'weight', 'view_mode']);
      foreach ($values as $row) {
        $q->values($row);
      }
      $q->execute();
    }

    $region->toggleUpdateStatus(false);

    return true;
  }

  return false;
}

/**
 * Save a layout.
 *
 * @param \Ucms\Layout\Layout $layout
 *   Layout to save.
 */
function ucms_layout_save(\Ucms\Layout\Layout $layout) {

  $tx = null;

  try {
    $tx = db_transaction();

    foreach ($layout->getAllRegions() as $region) {
      _ucms_layout_region_update($layout, $region);
    }

    // Value object that will get us the identifier then
    $row = new stdClass();
    $row->id = $layout->getId();
    $row->title_admin = $layout->getAdminTitle();
    $row->title = $layout->getTitle();
    $row->site_id = 0; // FIXME
    $row->uid = $layout->getAccountId();

    if (empty($row->uid)) {
      $row->uid = $GLOBALS['user']->uid;
    }

    if ($layout->getId()) {
      drupal_write_record('ucms_layout', $row, ['id']);
    } else {
      drupal_write_record('ucms_layout', $row);
    }

    $layout->getAccountId($row->uid);
    $layout->setId((int)$row->id);

    cache_clear_all('layout:' . $layout->getId(), 'cache_layout');

    unset($tx); // Explicit commit.

  } catch (\Exception $e) {
    try {
      if ($tx) {
        $tx->rollback();
      }
    } catch (\Exception $e) {}

    throw $e;
  }
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
  if ($layoutList = ucms_layout_load_multiple([$id])) {
    return reset($layoutList);
  }
}
