<?php

namespace MakinaCorpus\Ucms\Contrib\USync\Loading;

use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Contrib\USync\AST\Drupal\ContentTypeBehaviorNode;

use USync\AST\NodeInterface;
use USync\Context;
use USync\Loading\AbstractLoader;
use USync\TreeBuilding\ArrayTreeBuilder;

class ContentTypeBehaviorLoader extends AbstractLoader
{
    /**
     * @var ContentTypeManager
     */
    protected $contentTypeManager;

    /**
     * ContentTypeBehaviorLoader constructor.
     */
    public function __construct(ContentTypeManager $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return 'ucms_behavior';
    }

    /**
     * {@inheritdoc}
     */
    public function canProcess(NodeInterface $node)
    {
        return $node instanceof ContentTypeBehaviorNode;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(NodeInterface $node, Context $context)
    {
        return (boolean) $this->getExistingObject($node, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getExistingObject(NodeInterface $node, Context $context)
    {
        $contentType = $node->getAttribute('bundle');
        return $this->contentTypeManager->loadBehaviorsForType($contentType);
    }

    /**
     * {@inheritdoc}
     */
    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        $existing = $this->getExistingObject($node, $context);
        $builder = new ArrayTreeBuilder();
        foreach ($builder->parseWithoutRoot($existing) as $child) {
            $node->addChild($child);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $contentType = $node->getAttribute('bundle');
        $this->contentTypeManager->removeBehaviorsForType($contentType);
    }

    /**
     * {@inheritdoc}
     */
    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $contentType = $node->getAttribute('bundle');
        $behaviors = (array) $node->getValue();
        $reset = ($this->exists($node, $context) && !$node->isMerge());

        $this->contentTypeManager->saveBehaviorsForType($contentType, $behaviors, $reset);
    }
}