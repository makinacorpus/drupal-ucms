<?php

namespace MakinaCorpus\Ucms\Layout;

use MakinaCorpus\Ucms\Site\NodeManager;

/**
 * Layout storage using Drupal database.
 */
class DrupalStorage implements StorageInterface
{
    private $db;
    private $nodeManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param NodeManager $nodeManager
     */
    public function __construct(\DatabaseConnection $db, NodeManager $nodeManager = null)
    {
        $this->db = $db;
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadAll($idList)
    {
        $ret = [];

        $rows = $this
            ->db
            ->select('ucms_layout', 'l')
            ->fields('l')
            ->condition('l.id', $idList)
            ->execute()
            ->fetchAllAssoc('id')
        ;

        $data = $this
            ->db
            ->select('ucms_layout_data', 'd')
            ->fields('d')
            ->condition('d.layout_id', $idList)
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

            $ret[$row->id] = $layout;
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

            $this
                ->db
                ->delete('ucms_layout_data')
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
                $q = $this->db->insert('ucms_layout_data');
                $q->fields(['layout_id', 'region', 'nid', 'weight', 'view_mode']);
                foreach ($values as $row) {
                    $q->values($row);
                }
                $q->execute();
            }

            // And create the node references

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
            $tx = $this->db->startTransaction();

            // Value object that will get us the identifier then
            $row = [
              'site_id' => $layout->getSiteId(),
              'nid'     => $layout->getNodeId(),
            ];

            if ($layout->getId()) {

                $this
                    ->db
                    ->update('ucms_layout')
                    ->fields($row)
                    ->condition('id', $layout->getId())
                    ->execute()
                ;

            } else {

                $id = (int)$this
                    ->db
                    ->insert('ucms_layout')
                    ->fields($row)
                    ->execute()
                ;

                $layout->setId((int)$id);
            }

            $nodeIdList = [];

            foreach ($layout->getAllRegions() as $region) {
                $this->regionUpdate($layout, $region);

                // Collect nodes for referencing them
                $nodeIdList = array_merge($nodeIdList, $region->getAllNodeIds());
            }

            if ($this->nodeManager && $nodeIdList) {
                $this->nodeManager->createReferenceBulkInSite($layout->getSiteId(), $nodeIdList);
            }

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
            $tx = $this->db->startTransaction();

            $this
                ->db
                ->delete('ucms_layout')
                ->condition('id', $id)
                ->execute()
            ;

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
            $existsInSite = (bool)$this
                ->db
                ->query(
                    "SELECT 1 FROM {ucms_site_node} WHERE nid = :node_id AND site_id = :site_id",
                    [
                        ':node_id' => $nodeId,
                        ':site_id' => $siteId,
                    ]
                )
                ->fetchField()
            ;

            if ($existsInSite) {

                $layout = new Layout();
                $layout->setSiteId($siteId);
                $layout->setNodeId($nodeId);

                $this->save($layout);

                return $layout;
            }
        }
    }
}
