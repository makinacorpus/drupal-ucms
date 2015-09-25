<?php
/**
 * @file
 * Various maintainance index operations.
 */

/**
 * Mark all content for reindexing in an index.
 *
 * @param string $index
 */
function _ucms_search_index_reindex($index) {

  db_delete('ucms_search_status')
    ->condition('index_key', $index)
    ->execute()
  ;

  $query = db_select('node', 'n');
  $query->fields('n', ['nid']);
  $query->addExpression(':index', 'index_key', [':index' => $index]);
  $query->addExpression(1, 'needs_reindex');

  module_invoke_all('ucms_search_index_reindex', $index, $query);

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
function _ucms_search_node_index_field_to_fulltext($node, $field_name) {
  if (field_get_items('node', $node, $field_name)) {
    $build = field_view_field('node', $node, $field_name, 'full');
    return drupal_render($build);
  }
}

/**
 * Index a single node
 *
 * @param string $index
 * @param stdClass $node
 */
function _ucms_search_node_index($index, $node) {

  $body     = strip_tags(_ucms_search_node_index_field_to_fulltext($node, 'body'));
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
