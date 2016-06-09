<?php

namespace MakinaCorpus\Ucms\Layout;

/**
 * Layout storage using Drupal database.
 */
class DrupalStorage implements StorageInterface
{
    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     */
    public function __construct(\DatabaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll($idList)
    {
        $ret = [];

        $cidList = array_map(
            function ($value) {
                return 'layout:' . $value;
            },
            $idList
        );

        foreach (cache_get_multiple($cidList, 'cache_layout') as $cached) {
            if ($cached->data instanceof Layout) {
                $ret[$cached->data->getId()] = $cached->data;
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

            // Build a structure map for easier and quicker lookup then
            $regionMap = [];
            foreach ($data as $line) {
                $regionMap[$line->layout_id][$line->region][] = new Item($line->nid, $line->view_mode);
            }

            foreach ($rows as $row) {

                $layout = (new Layout())
                    ->setId($row->id)
                    ->setSiteId($row->site_id)
                    ->setNodeId($row->nid)
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
     * Update a single region of a layout
     *
     * You should never call this method directly.
     *
     * @param Layout $layout
     *   Layout to save
     * @param string $region
     *   Set this to update only one region
     *
     * @return boolean
     *   True if the instance was saved, false if not modified
     */
    protected function regionUpdate(Layout $layout, Region $region)
    {
        if ($region->isUpdated()) {

            db_delete('ucms_layout_data')
                ->condition('layout_id', $layout->getId())
                ->condition('region', $region->getName())
                ->execute()
            ;

            $values = [];

            /* @var $item Item */
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
     * {@inheritdoc}
     */
    public function save(Layout $layout)
    {
        $tx = null;

        try {
            $tx = db_transaction();

            // Value object that will get us the identifier then
            $row              = new \stdClass();
            $row->site_id     = $layout->getSiteId();
            $row->nid         = $layout->getNodeId();

            /* if (empty($row->nid)) {
                $row->nid = $GLOBALS['user']->nid;
            } */

            if ($layout->getId()) {
                $row->id = $layout->getId();
                drupal_write_record('ucms_layout', $row, ['id']);
            } else {
                drupal_write_record('ucms_layout', $row);
                $layout->setId((int)$row->id);
            }

            // Update region
            foreach ($layout->getAllRegions() as $region) {
                $this->regionUpdate($layout, $region);
            }

            cache_clear_all('layout:' . $layout->getId(), 'cache_layout');

            unset($tx); // Explicit commit

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
     * {@inheritdoc}
     */
    public function delete($id)
    {
        if ($id instanceof Layout) {
            $id = $id->getId();
        }

        $tx = null;

        try {
          $tx = db_transaction();

            // We miss a few ON DELETE CASCADE
            db_delete('ucms_layout_data')
                ->condition('layout_id', $id)
                ->execute()
            ;

            db_delete('ucms_layout')
                ->condition('id', $id)
                ->execute()
            ;

            cache_clear_all('layout:' . $id, 'cache_layout');

            unset($tx); // Explicit commit

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
     * {@inheritdoc}
     */
    public function load($id)
    {
        if ($layoutList = $this->loadAll([$id])) {
            return reset($layoutList);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findForNodeOnSite($nodeId, $siteId, $createOnMiss = false)
    {
        // @todo Find a more performant way, this is still better than do it
        // once per loaded node in the hook_node_load()...
        $id = (int)$this
            ->db
            ->query(
                "SELECT id FROM {ucms_layout} WHERE nid = ? AND site_id = ?",
                [$nodeId, $siteId]
            )
            ->fetchField()
        ;

        if ($id) {
            return $this->load($id);
        }

        if ($createOnMiss) {

            // Else we will experience foreign key constraint violation
            $existsInSite = (bool)db_query("SELECT 1 FROM {ucms_site_node} WHERE nid = :node_id AND site_id = :site_id", [
                ':node_id' => $nodeId,
                ':site_id' => $siteId,
            ])->fetchField();

            if ($existsInSite) {
                $id = (int)db_insert('ucms_layout')
                    ->fields([
                        'nid'     => $nodeId,
                        'site_id' => $siteId
                    ])
                    ->execute()
                ;

                return (new Layout())
                    ->setId($id)
                    ->setSiteId($siteId)
                    ->setNodeId($nodeId)
                ;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetCacheForNode($nodeId)
    {
        cache_clear_all('*', 'cache_layout', true);
    }

    /**
     * {@inheritdoc}
     */
    public function resetCacheForSite($siteId)
    {
        cache_clear_all('*', 'cache_layout', true);
    }
}
