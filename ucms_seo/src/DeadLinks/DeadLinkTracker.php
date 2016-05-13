<?php

namespace MakinaCorpus\Ucms\Seo\DeadLinks;

use Drupal\node\NodeInterface;
use MakinaCorpus\ULink\EntityLinkFilter;

class DeadLinkTracker
{
    private $node;

    public function __construct(NodeInterface $node)
    {
        $this->node = $node;
    }

    public function track()
    {
        $data = [];

        $fields = $this->getSearchableFields();
        foreach ($fields as $field) {
            $items = field_get_items('node', $this->node, $field);
            if ($items) {

                $extracted = $this->extractData($items);
                if ($extracted) {
                    foreach ($extracted as $destination_nid => $row) {
                        $data[] = [
                            'source_nid' => $this->node->nid,
                            'source_field' => $field,
                        ] + $row;
                    }
                }
            }
        }

        if ($data) {
            $trx = db_transaction();
            try {
                $this->clearTracking();
                $this->insertTracking($data);
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

            if (in_array(
                $field['type'],
                ['text', 'text_long', 'text_with_summary'],
                true
            )) {
                $fields[] = $fieldname;
            }
        }

        return $fields;
    }

    private function extractData(array $items)
    {
        $data = [];
        foreach ($items as $delta => $values) {

            // FIXME - HARDCODED for now, see EntityLinkFilter::process()
            $matches = [];
            if (preg_match_all(
                EntityLinkFilter::SCHEME_REGEX,
                $values['value'],
                $matches
            )) {
                foreach ($matches[0] as $index => $match) {
                    $nid = $matches[3][$index];
                    $data[$nid] = [
                        'destination_nid' => $nid,
                        'destination_url' => $match,
                    ];
                }
            }

            $matches = [];
            if (preg_match_all(
                EntityLinkFilter::MOUSTACHE_REGEX,
                $values['value'],
                $matches
            )) {
                foreach ($matches[0] as $index => $match) {
                    $nid = $matches[2][$index];
                    $data[$nid] = [
                        'destination_nid' => $nid,
                        'destination_url' => $match,
                    ];
                }
            }
        }

        return $data;
    }

    private function clearTracking()
    {
        db_delete('ucms_seo_deadlinks_tracking')
            ->condition('source_nid', $this->node->nid)
            ->execute();
    }

    private function insertTracking(array $data)
    {
        $query = db_insert('ucms_seo_deadlinks_tracking')
            ->fields(array_keys(reset($data)));

        foreach ($data as $row) {
            $query->values($row);
        }

        $query->execute();
    }
}
