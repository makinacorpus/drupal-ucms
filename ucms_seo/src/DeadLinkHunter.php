<?php

namespace MakinaCorpus\Ucms\Seo;

use Drupal\node\NodeInterface;
use MakinaCorpus\ULink\EntityLinkFilter;

class DeadLinkHunter
{
  private $node;

  public function __construct(NodeInterface $node)
  {
    $this->node = $node;
  }

  public function track()
  {
    $nids = [];

    $fields = $this->getSearchableFields();
    foreach ($fields as $field) {
      $items = field_get_items('node', $this->node, $field);
      if ($items) {
        $nids = array_merge($nids, $this->extractNodeReferences($items));
      }
    }
    $nids = array_unique($nids);

    if ($nids) {
      $trx = db_transaction();
      try {
        $this->clearTracking();
        $this->insertTracking($nids);
      } catch (\PDOException $e) {
        $trx->rollback();
        // Silent fail to continue saving node.
        watchdog(
          'ucms_seo_deadlink',
          "Unable to save reference for node source '!nid' with exception: !msg",
          [
            '!nid' => $this->node->nid,
            '!msg' => $e->getMessage(),
          ]
        );
      }
      unset($trx);
    }
  }

  public function untrack()
  {
    $this->clearTracking();
  }

  private function getSearchableFields()
  {
    $fields = [];

    $fields_info = field_info_field_map();
    $info = field_info_instances('node', $this->node->type);
    foreach (array_keys($info) as $fieldname) {
      $field = $fields_info[$fieldname];

      if (in_array($field['type'], ['text', 'text_long', 'text_with_summary'], true)) {
        $fields[] = $fieldname;
      }
    }

    return $fields;
  }

  private function extractNodeReferences(array $items)
  {
    $nids = [];

    foreach ($items as $delta => $data) {
      // FIXME - HARDCODED for now, see EntityLinkFilter::process()
      $matches = [];
      if (preg_match_all(EntityLinkFilter::SCHEME_REGEX, $data['value'], $matches)) {
        $nids = array_merge($nids, $matches[3]);
      }
      $matches = [];
      if (preg_match_all(EntityLinkFilter::MOUSTACHE_REGEX, $data['value'], $matches)) {
        $nids = array_merge($nids, $matches[2]);
      }
    }

    return $nids;
  }

  private function clearTracking()
  {
    db_delete('ucms_seo_deadlinks_tracking')
      ->condition('source_nid', $this->node->nid)
      ->execute();
  }

  private function insertTracking(array $nids)
  {
    $query = db_insert('ucms_seo_deadlinks_tracking')
      ->fields(['source_nid', 'destination_nid']);

    foreach ($nids as $nid) {
      $query->values([
        'source_nid' => $this->node->nid,
        'destination_nid' => $nid,
      ]);
    }

    $query->execute();
  }
}
