<?php
/**
 * @file
 * Various maintainance index operations.
 *
 * @todo
 *   Use the bulk API.
 */

/**
 * Dequeue and index items.
 */
function ucms_search_cron_index_dequeue($index, $limit = UCMS_SEARCH_CRON_INDEX_LIMIT) {

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
  foreach ($nodes as $node) {
    ucms_search_index_node_add($index, $node);
  }

  $toBeDeleted = array_diff(array_keys($nodes), $nodeIdList);
  foreach ($toBeDeleted as $nid) {
    ucms_search_index_node_delete($index, $nid);
  }
}

/**
 * Clear an index.
 *
 * @param string $index
 */
function ucms_search_index_clear($index, $doReindex = false) {
  require_once __DIR__ . '/ucms_search.index.inc';
  _ucms_search_index_delete($index);
  _ucms_search_index_create($index);
  if ($doReindex) {
    return ucms_search_index_reindex($index);
  }
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
function _ucms_search_index_create($index, $params = []) {
  $namespace = ucms_search_client_get()->indices();
  if (!$namespace->exists(['index' => $index])) {
    $namespace->create([
      'index' => $index,
      'body' => [
        'settings' => [
          'number_of_shards' => 1,
          'number_of_replicas' => 0,
        ],
        'mappings' => [
          'node' => [
            'properties' => [
              'title' => [
                'type'        => 'string',
                'analyzer'    => 'standard',
                'term_vector' => 'yes',
                'copy_to'     => 'combined'
              ],
              'body' => [
                'type'        => 'string',
                'analyzer'    => 'standard',
                'term_vector' => 'yes',
                'copy_to'     => 'combined'
              ],
              'combined' => [
                'type'        => 'string',
                'analyzer'    => 'standard',
                'term_vector' => 'yes'
              ],
              'id' => [
                'type' => 'long',
              ],
              'owner' => [
                'type' => 'long',
              ],
              'created' => [
                'type' => 'date',
              ],
              'updated' => [
                'type' => 'date',
              ],
              'tags' => [
                'type' => 'integer',
              ],
            ]
          ]
        ]
      ],
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
 * Remove a single node from index
 *
 * @param string $index
 * @param int $nid
 */
function ucms_search_index_node_delete($index, $nid) {
return; // FIXME (throwing exceptions)
  ucms_search_client_get()
    ->delete([
      'index' => $index,
      'id'    => $nid,
      'type'  => 'node',
    ])
  ;

  db_delete('ucms_search_status')
    ->condition('nid', $nid)
    ->condition('index_key', $index)
    ->execute()
  ;
}

/**
 * Index a single node
 *
 * @param string $index
 * @param stdClass $nodes
 */
function ucms_search_index_node_add($index, $node) {

  $body     = strip_tags(_ucms_search_index_node_field_to_fulltext($node, 'body'));
  $tags     = _ucms_search_index_node_field_to_tag_id_list($node, 'tags');
  $created  = null;
  $changed  = null;

  try {
    $created = new DateTime('@' . $node->created);
  } catch (Exception $e) {}
  try {
    $changed = new DateTime('@' . $node->changed);
  } catch (Exception $e) {}

  ucms_search_client_get()
    ->index([
      'index' => $index,
      'id'    => $node->nid,
      'type'  => 'node',
      'body'  => [
        'title'   => $node->title,
        'id'      => $node->nid,
        'owner'   => $node->uid,
        'created' => $created->format(DateTime::ISO8601),
        'updated' => $changed->format(DateTime::ISO8601),
        'type'    => $node->type,
        'body'    => $body,
        'tags'    => $tags,
      ],
    ])
  ;

  db_update('ucms_search_status')
    ->fields(['needs_reindex' => 0])
    ->condition('nid', $node->nid)
    ->condition('index_key', $index)
    ->execute()
  ;
}
