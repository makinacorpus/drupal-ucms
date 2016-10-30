<?php

namespace MakinaCorpus\Ucms\Tag;

/**
 * Proudly presents the tag storage; simple, efficient.
 */
class TagStorage
{
    private $database;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $database
     */
    public function __construct(\DatabaseConnection $database)
    {
        $this->database = $database;
    }

    /**
     * Find one or more existing tags
     *
     * @param string|string[] $names
     *
     * @return Tag[]
     */
    private function find($names)
    {
        return $this
            ->database
            ->query(
                "SELECT * FROM {ucms_tag} WHERE name IN (:names)",
                [':names' => $names]
            )
            ->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, Tag::class)
        ;
    }

    /**
     * Create or merge tag
     *
     * @param string $name
     *
     * @return Tag
     */
    public function merge($name)
    {
        $tags = $this->mergeAll([$name]);

        return reset($tags);
    }

    /**
     * Create or merge a set of tags
     *
     * @param string[] $name
     *
     * @return Tag[]
     */
    public function mergeAll(array $names)
    {
        if (!$names) {
            return [];
        }

        $tags = $this->find($names);

        // Drop existing from the insert list
        foreach ($tags as $tag) {
            $pos = array_search($tag->getName(), $names);
            if (false !== $pos) {
                unset($names[$pos]);
            }
        }

        if ($names) {
            foreach ($names as $name) {

                if (!is_string($name) || empty($name)) {
                    throw new \InvalidArgumentException(sprintf("tag name must be a non empty string"));
                }

                // Drupal actualy cant return a set of inserted identifiers when
                // doing a bulk insert, so there is no point in doing it bulk.
                // Moreover, it actually emulate bulk inserts by doing all inserts
                // one by one in a transaction (seriously, what the fuck Drupal).
                $id = $this
                    ->database
                    ->insert('ucms_tag')
                    ->fields(['name' => $name])
                    ->execute()
                ;

                $tags[] = new Tag($id, $name);
            }
        }

        return $tags;
    }

    /**
     * Delete a single tag or a set of tags
     *
     * @param string|string[] $name
     *
     * @return int
     *   Number of item deleted
     */
    public function delete($name)
    {
        if (!$name) {
            return 0;
        }

        return (int)$this
            ->database
            ->query(
                "DELETE FROM {ucms_tag} WHERE name IN (:names)",
                [':names' => $name],
                ['return' => \Database::RETURN_AFFECTED]
            )
        ;
    }

    /**
     * Attach any number of tags to any number of nodes
     *
     * @param int|int[] $nodeId
     * @param string|string[] $name
     */
    public function attach($nodeId, $name)
    {
        if (!$nodeId || !$name) {
            return;
        }


    }

    /**
     * Detach any number of tags from any number of nodes
     *
     * @param int|int[] $nodeId
     * @param string|string[] $name
     */
    public function detach($nodeId, $name)
    {
        if (!$nodeId || !$name) {
            return;
        }

        $q = $this
            ->database
            ->select('ucms_tag', 't')
            ->fields('t', ['id'])
            ->condition($name)
        ;

        $this
            ->database
            ->delete('ucms_tag_node')
            ->condition('node_id', $nodeId)
            ->condition('tag_id', $q)
            ->execute()
        ;
    }
}
