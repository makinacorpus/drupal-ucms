<?php

namespace MakinaCorpus\Ucms\Drop\Impl;

use MakinaCorpus\Ucms\Drop\DragItem;
use MakinaCorpus\Ucms\Drop\DropHandlerInterface;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\NodeInterface;

abstract class AbstractNodeDropHandler implements DropHandlerInterface
{
    protected $entityManager;
    protected $bundle;
    protected $defaultViewMode;
    protected $allowedViewModes;

    /**
     * Default constructor
     *
     * @param string[] $bundles
     *   Accepted bundle list
     */
    public function __construct(
        EntityManager $entityManager,
        array $bundles = null,
        $defaultViewMode = null,
        array $allowedViewModes = null
    ) {
        $this->entityManager = $entityManager;
        $this->bundles = $bundles;
        $this->defaultViewMode = $defaultViewMode;
        $this->allowedViewModes = $allowedViewModes;
    }

    /**
     * Get node from the given drag item
     *
     * @param DragItem $target
     *
     * @return NodeInterface
     */
    protected function getNodeFromDragItem(DragItem $target)
    {
        return $this->entityManager->getStorage('node')->load($target->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function accepts($id, DragItem $target)
    {
        if ('node' !== $target->getType()) {
            return false;
        }

        if ($this->bundle) {
            $node = $this->getNodeFromDragItem($target);

            if (!$node) {
                return false;
            }

            return in_array($node->bundle(), $this->bundle);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function render($id, DragItem $target, $options = [])
    {
        $viewMode = null;
        $node = $this->getNodeFromDragItem($target);

        if (!$node) {
            throw new \InvalidArgumentException(sprintf("'%d' node does not exist", $target->getId()));
        }

        if (isset($options['view_mode'])) {
            $viewMode = $options['view_mode'];

            if ($this->allowedViewModes && !in_array($viewMode, $this->allowedViewModes)) {
                throw new \InvalidArgumentException(sprintf("'%s' is not an allowed view mode", $viewMode));
            }
        }

        if (!$viewMode && $this->defaultViewMode) {
            $viewMode = $this->defaultViewMode;
        }

        $build = node_view($node, $viewMode);

        return drupal_render($build);
    }
}
