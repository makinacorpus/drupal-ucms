<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Contrib\NodeReference;

use Symfony\Component\EventDispatcher\GenericEvent;

class NodeReferenceCollectEvent extends GenericEvent
{
    const EVENT_NAME = 'ucms.node.collect';

    /**
     * @var int[][]
     */
    private $references = [];

    /**
     * Default constructor
     *
     * @param NodeInterface $node
     */
    public function __construct(NodeInterface $node)
    {
        parent::__construct($node);
    }

    /**
     * Get node
     *
     * @return NodeInterface
     */
    public function getNode()
    {
        return $this->getSubject();
    }

    /**
     * Add one or more references
     *
     * @param int|int[] $nodeIdList
     *   Node identifiers list
     * @param string $type
     *   Reference type for business purposes, 'media', 'link' or any other
     * @param string $field
     *   Field name this reference was found into
     */
    public function addReferences($type, $nodeIdList, $field = null)
    {
        if (!is_array($nodeIdList)) {
            $nodeIdList = [$nodeIdList];
        }

        $sourceId = $this->getNode()->id();

        foreach ($nodeIdList as $nodeId) {
            $this->references[$type . $nodeId] = new NodeReference($sourceId, $nodeId, $type, $field);
        }
    }

    /**
     * Get all references
     *
     * @return NodeReference[]
     *   There will no duplicates
     */
    public function getReferences()
    {
        return $this->references;
    }
}
