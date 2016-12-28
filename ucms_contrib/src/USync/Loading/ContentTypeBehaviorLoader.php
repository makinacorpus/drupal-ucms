<?php

namespace MakinaCorpus\Ucms\Contrib\USync\Loading;

use MakinaCorpus\Ucms\Contrib\Behavior\ContentTypeBehavior;
use MakinaCorpus\Ucms\Contrib\Behavior\ContentTypeBehaviorInterface;
use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use MakinaCorpus\Ucms\Contrib\EventDispatcher\BehaviorCollectionEvent;
use MakinaCorpus\Ucms\Contrib\USync\AST\Drupal\ContentTypeBehaviorNode;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var string[]|null
     */
    protected $availableBehaviors;

    /**
     * ContentTypeBehaviorLoader constructor.
     *
     * @param ContentTypeManager $contentTypeManager
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(ContentTypeManager $contentTypeManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->contentTypeManager = $contentTypeManager;
        $this->eventDispatcher = $eventDispatcher;
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

        // Filters potential invalid behaviors.
        $behaviors = array_intersect($behaviors, $this->getAvailableBehaviors());

        $this->contentTypeManager->saveBehaviorsForType($contentType, $behaviors, $reset);
    }

    /**
     * Collects all available content type behaviors and returns their
     * identifiers.
     *
     * @return string[]
     */
    protected function getAvailableBehaviors()
    {
        if ($this->availableBehaviors === null) {
            $event = new BehaviorCollectionEvent();
            $this->eventDispatcher->dispatch(BehaviorCollectionEvent::EVENT_NAME, $event);
            $this->availableBehaviors = $event->getBehaviorsIdentifiers();
        }
        return $this->availableBehaviors;
    }
}
