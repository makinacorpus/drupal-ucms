<?php
/**
 * @file
 * Various maintainance index operations.
 */

/**
 * Create or update index definition.
 *
 * @param string $index
 *   Index key.
 * @param string $name
 *   Human readable title.
 * @param array $param
 *   This must contain the exact replica of the 'body' key that will be sent
 *   to ElasticSearch indices()::create() array.
 * @param boolean $force
 *   Force index to be refreshed.
 */
function ucms_search_index_definition_save($index, $name, $param, $force = false) {

  $updated  = false;
  $existing = ucms_search_index_definition_load($index);

  // Directly compare array structures, should be enough
  if (!$existing || $force || $param !== $existing) {
    // This was updated, then we really need to save it
    $updated = true;
  }

  db_merge('ucms_search_index')
    ->key(['index_key' => $index])
    ->fields(['name' => $name, 'data' => serialize($param)])
    ->execute()
  ;

  ucms_search_index_definition_cache_clear();
  module_invoke_all('ucms_search_index_definition_save', $index, $param, $updated, !$existing);

  if ($updated) {
    ucms_search_index_clear($index);
  }
}

/**
 * Get node indexes.
 *
 * @return string[]
 */
function ucms_search_index_list() {

  $list = &drupal_static(__FUNCTION__);
  if (null !== $list) {
    return $list;
  }

  // Attempt to have a zero SQL Drupal.
  $cache = variable_get('ucms_search_cache_list', true);

  if ($cache && ($cached = cache_get('ucms_search_index_list', 'cache_bootstrap'))) {
    $list = $cached->data;
  } else {
    $list = db_query("SELECT index_key, name FROM {ucms_search_index}")->fetchAllKeyed();
    if ($cache) {
      cache_set('ucms_search_index_list', $list, 'cache_bootstrap');
    }
  }

  return $list;
}

/**
 * Load an index definition
 *
 * This is uncached, never use it during normal runtime
 *
 * @param string $index
 *
 * @return array
 *   This must contain the exact replica of the 'body' key that will be sent
 *   to ElasticSearch indices()::create() array.
 */
function ucms_search_index_definition_load($index) {

  // Minor optimization, avoid lookups for unknow indexes.
  $list = ucms_search_index_list();
  if (!isset($list[$index])) {
    return;
  }

  $cache = &drupal_static(__FUNCTION__, []);

  // Can be null if index does not exists.
  if (array_key_exists($index, $cache)) {
    return $cache[$index];
  }

  $param = db_query("SELECT data FROM {ucms_search_index} WHERE index_key = :index", ['index' => $index])->fetchField();

  if ($param) {
    $param = unserialize($param);
  }

  return $cache[$index] = $param;
}

/**
 * Delete an index definition
 *
 * @param string $index
 */
function ucms_search_index_definition_delete($index) {
  db_delete('ucms_search_index')
    ->condition('index_key', $index)
    ->execute()
  ;
  ucms_search_index_definition_cache_clear();
  module_invoke_all('ucms_search_index_definition_delete', $index);
  _ucms_search_index_delete($index);
}

/**
 * Clear all index definition related cache.
 */
function ucms_search_index_definition_cache_clear() {
  drupal_static_reset('ucms_search_index_list');
  drupal_static_reset('ucms_search_index_definition_load');
  cache_clear_all('ucms_search_index_list', 'cache_bootstrap');
}

/**
 * Dequeue and index items.
 */
function ucms_search_index_dequeue_bulk($index, $limit = null) {

  if (!$limit) {
    $limit = variable_get('ucms_search_cron_limit', UCMS_SEARCH_CRON_INDEX_LIMIT);
  }

  $nodeIdList = db_select('ucms_search_status', 's')
    ->fields('s', ['nid'])
    ->condition('s.needs_reindex', 1)
    ->condition('s.index_key', $index)
    ->range(0, $limit)
    ->execute()
    ->fetchCol()
  ;

  // Preload all nodes for performance.
  $nodes = node_load_multiple($nodeIdList);
  if (!$nodes) {
    return 0;
  }
  $count = count($nodes);

  ucms_search_index_node_index_bulk($index, $nodes, true, false);

  $toBeDeleted = array_diff(array_keys($nodes), $nodeIdList);
  foreach ($toBeDeleted as $nid) {
    ucms_search_index_node_delete($index, $nid);
  }

  return $count;
}

/**
 * Clear an index.
 *
 * @param string $index
 */
function ucms_search_index_clear($index) {
  _ucms_search_index_delete($index);
  _ucms_search_index_create($index);
  return ucms_search_index_reindex($index);
}

/**
 * Mark all content for reindexing in an index.
 *
 * @param string $index
 * @param int|int[] $nidList
 *   List of node identifiers to reindex or delete
 */
function ucms_search_index_reindex($index, $nidList = null) {

  $deleteQuery = db_delete('ucms_search_status')
    ->condition('index_key', $index)
  ;
  if (null !== $nidList) {
    $deleteQuery->condition('nid', $nidList);
  }
  $deleteQuery->execute();

  $query = db_select('node', 'n');
  $query->fields('n', ['nid']);
  $query->addExpression(':index', 'index_key', [':index' => $index]);
  $query->addExpression(1, 'needs_reindex');

  module_invoke_all('ucms_search_index_reindex', $index, $query);

  if (null !== $nidList) {
    $query->condition('n.nid', $nidList);
  }

  db_insert('ucms_search_status')
    ->from($query)
    ->execute()
  ;
}

/**
 * Ensure indexes exists.
 *
 * @param string $index
 * @param mixed[] $params
 */
function _ucms_search_index_delete($index) {
  $namespace = ucms_search_client_get()->indices();
  if ($namespace->exists(['index' => $index])) {
    $namespace->delete(['index' => $index]);
  }
}

/**
 * Ensure indexes exists.
 *
 * @param string $index
 * @param mixed[] $params
 */
function _ucms_search_index_create($index) {
  $param = ucms_search_index_definition_load($index);
  if (!$param) {
    throw new \InvalidArgumentException(sprintf("'%s' index definition does not exist", $index));
  }
  $namespace = ucms_search_client_get()->indices();
  if (!$namespace->exists(['index' => $index])) {
    $namespace->create([
      'index' => $index,
      'body'  => $param,
    ]);
  }
}

/**
 * Extract textual data from content
 *
 * @param stdClass $node
 * @param string $field_name
 *
 * @return string
 */
function _ucms_search_index_node_field_to_fulltext($node, $field_name) {
  if (field_get_items('node', $node, $field_name)) {
    $build = field_view_field('node', $node, $field_name, 'full');
    return drupal_render($build);
  }
}

/**
 * Extract term identifiers from field
 *
 * @param stdClass $node
 * @param string $field_name
 *
 * @return int[]
 */
function _ucms_search_index_node_field_to_tag_id_list($node, $field_name) {
  $ret = [];
  if ($items = field_get_items('node', $node, $field_name)) {
    foreach ($items as $item) {
      if (isset($item['tid'])) {
        $ret[] = (int)$item['tid'];
      }
    }
  }
  return $ret;
}

/**
 * Process all fields of the given node and return an elastic friendly array.
 *
 * @param stdClass $node
 *
 * @return array
 */
function _ucms_search_index_node_field_process($node) {

  $created  = null;
  $changed  = null;

  try {
    $created = new DateTime('@' . $node->created);
  } catch (Exception $e) {}
  try {
    $changed = new DateTime('@' . $node->changed);
  } catch (Exception $e) {}

  return [
    'title'   => $node->title,
    'id'      => $node->nid,
    'owner'   => $node->uid,
    'created' => $created ? $created->format(DateTime::ISO8601) : null,
    'updated' => $changed ? $changed->format(DateTime::ISO8601) : null,
    'type'    => $node->type,
    'body'    => strip_tags(_ucms_search_index_node_field_to_fulltext($node, 'body')),
    'status'  => (int)$node->status,
    'tags'    => _ucms_search_index_node_field_to_tag_id_list($node, 'tags'),
  ];
}

/**
 * Remove a single node from index
 *
 * @param string $index
 * @param int $nid
 */
function ucms_search_index_node_delete($index, $nid) {

  return; // FIXME (throwing exceptions)

  $response = ucms_search_client_get()
    ->delete([
      'index' => $index,
      'id'    => $nid,
      'type'  => 'node',
    ])
  ;

  if (UCMS_SEARCH_ELASTIC_DEBUG) {
    watchdog(__FUNCTION__, '<pre>' . print_r($response, true) . '</pre>', null, WATCHDOG_DEBUG);
  }

  db_delete('ucms_search_status')
    ->condition('nid', $nid)
    ->condition('index_key', $index)
    ->execute()
  ;
}

/**
 * Tell if the given node matches the given index.
 *
 * @param string $index
 * @param stdClass $node
 *
 * @return boolean
 */
function ucms_search_index_node_matches($index, $node) {
  foreach (module_implements('ucms_search_index_node') as $module) {
    if (module_invoke($module, 'ucms_search_index_node', $index, $node)) {
      return true;
    }
  }
  return false;
}

/**
 * Index or upsert given nodes using a bulk request.
 *
 * Note that Elastic Search will be as fast if you choose to do an explicit
 * upsert or attempt to index a document with an already existing identifier,
 * reason why we actually choose to only send index operations, this way we
 * don't have to check ourselves if the document exists in the index or not.
 * Elastic Search index operation is actually an upsert operation.
 *
 * @param string $index
 *   Index identifier.
 * @param stdClass[] $nodeList
 *   Node object.
 * @param boolean $force
 *   Internal boolean, skip match test, this should never be used
 *   outside of this module.
 * @param boolean $refresh
 *   Explicit refresh set to true for ElasticSearch, forcing the full shard
 *   to be in sync for the next search.
 */
function ucms_search_index_node_index_bulk($index, $nodeList, $force = false, $refresh = false) {

  if (empty($nodeList)) {
    return;
  }

  $params   = [];
  $nidList  = [];

  foreach ($nodeList as $key => $node) {

    if (!$force && !ucms_search_index_node_matches($index, $node)) {
      unset($nodeList[$key]);
    }

    $params['body'][] = [
      'index' => [
        '_index'   => $index,
        '_id'      => $node->nid,
        '_type'    => 'node',
        // @todo Refresh could be global.
        // '_refresh' => (bool)$refresh,
      ],
    ];

    $params['body'][] = _ucms_search_index_node_field_process($node);

    $nidList[] = $node->nid;
  }

  $response = ucms_search_client_get()->bulk($params);

  if (UCMS_SEARCH_ELASTIC_DEBUG) {
    watchdog(__FUNCTION__, '<pre>' . print_r($response, true) . '</pre>', null, WATCHDOG_DEBUG);
  }

  db_update('ucms_search_status')
    ->fields(['needs_reindex' => 0])
    ->condition('nid', $nidList)
    ->condition('index_key', $index)
    ->execute()
  ;
}

/**
 * Index or upsert a single node.
 *
 * Note that Elastic Search will be as fast if you choose to do an explicit
 * upsert or attempt to index a document with an already existing identifier,
 * reason why we actually choose to only send index operations, this way we
 * don't have to check ourselves if the document exists in the index or not.
 * Elastic Search index operation is actually an upsert operation.
 *
 * @param string $index
 *   Index identifier.
 * @param stdClass $node
 *   Node object.
 * @param boolean $force
 *   Internal boolean, skip match test, this should never be used
 *   outside of this module.
 * @param boolean $refresh
 *   Explicit refresh set to true for ElasticSearch, forcing the full shard
 *   to be in sync for the next search.
 *
 * @return boolean
 *   True if the index command has been sent or false if node was dropped.
 */
function ucms_search_index_node_index($index, $node, $force = false, $refresh = false) {

  if (!$force && !ucms_search_index_node_matches($index, $node)) {
    return false;
  }

  $response = ucms_search_client_get()
    ->index([
      'index'   => $index,
      'id'      => $node->nid,
      'type'    => 'node',
      'refresh' => (bool)$refresh,
      'body'    => _ucms_search_index_node_field_process($node),
    ])
  ;

  if (UCMS_SEARCH_ELASTIC_DEBUG) {
    watchdog(__FUNCTION__, '<pre>' . print_r($response, true) . '</pre>', null, WATCHDOG_DEBUG);
  }

  db_update('ucms_search_status')
    ->fields(['needs_reindex' => 0])
    ->condition('nid', $node->nid)
    ->condition('index_key', $index)
    ->execute()
  ;

  return true;
}
