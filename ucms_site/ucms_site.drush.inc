<?php

use MakinaCorpus\Ucms\Site\EventDispatcher\NodeReferenceCollectEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\NodeReference;

/**
 * Implements hook_drush_command().
 */
function ucms_site_drush_command() {
  $items = [];

  $items['node-access-rebuild'] = [
    'callback'    => 'ucms_site_drush_node_access_rebuild',
    'description' => "Rebuild node access for all nodes.",
    'bootstrap'   => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  ];
  $items['node-references'] = [
    'callback'    => 'ucms_site_drush_node_references',
    'description' => "Force all node references recollection.",
    'bootstrap'   => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  ];

  return $items;
}

/**
 * Rebuild all node grants.
 */
function ucms_site_drush_node_access_rebuild() {

  if (!drush_confirm("This may cause temporary display issues on the site, continue ?")) {
    return;
  }

  $total  = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
  if (!$total) {
    drush_print("No node access to rebuild.");
    return;
  }

  $limit  = 50;
  $done   = 0;
  $latest = 0;
  $bar    = \DrushProgressBar::create();

  // Got a lot of nodes, so let's load them bulk.
  $bar->start();
  do {
    $idList = db_query("SELECT nid FROM {node} WHERE nid > :max ORDER BY nid ASC LIMIT " . $limit, [':max' => $latest])->fetchCol();

    foreach (node_load_multiple($idList) as $node) {
      $latest = $node->nid;

      node_access_acquire_grants($node);
    }

    $done += count($idList);
    $bar->update($done / $total);

  } while ($idList);

  $bar->end();
}

/**
 * Rebuild all node grants.
 */
function ucms_site_drush_node_references() {

  if (!drush_confirm("This may be a bit long, continue ?")) {
    return;
  }

  $total  = db_query("SELECT COUNT(*) FROM {node}")->fetchField();
  if (!$total) {
    drush_print("No node to collect.");
    return;
  }

  $limit  = 50;
  $done   = 0;
  $latest = 0;
  $bar    = \DrushProgressBar::create();

  $dispatcher = \Drupal::service('event_dispatcher');
  $manager = \Drupal::service('ucms_site.node_manager');

  // Got a lot of nodes, so let's load them bulk.
  $bar->start();
  do {
    $idList = db_query("SELECT nid FROM {node} WHERE nid > :max ORDER BY nid ASC LIMIT " . $limit, [':max' => $latest])->fetchCol();

    foreach (node_load_multiple($idList) as $node) {
        $latest = $node->nid;

        $event = new NodeReferenceCollectEvent($node);
        $dispatcher->dispatch(NodeReferenceCollectEvent::EVENT_NAME, $event);
        if ($node->ucms_sites) {
            $references = [];
            $references[] = $node->id();
            foreach ($event->getReferences() as $reference) {
                if (NodeReference::TYPE_MEDIA !== $reference->getType()) {
                    $references[] = $reference->getTargetId();
                }
            }
            if ($references) {
                foreach ($node->ucms_sites as $siteId) {
                    $manager->createReferenceBulkInSite($siteId, $references);
                }
            }
        }
    }

    $done += count($idList);
    $bar->update($done / $total);

  } while ($idList);

  $bar->end();
}
