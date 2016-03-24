<?php

namespace MakinaCorpus\Ucms\Search\USync;

use USync\AST\NodeInterface;
use USync\Context;
use USync\TreeBuilding\ArrayTreeBuilder;
use USync\Loading\AbstractLoader;

class IndexLoader extends AbstractLoader
{
    public function getType()
    {
        return 'ucms_search_index';
    }

    public function exists(NodeInterface $node, Context $context)
    {
        return ucms_search_index()->exists($node->getName());
    }

    public function getExistingObject(NodeInterface $node, Context $context)
    {
        $param = ucms_search_index()->load($node->getName());

        if ($param) {
            // FIXME Missing 'name'
            return $param;
        }

        $context->logCritical(sprintf("%s: does not exist", $node->getPath()));
    }

    public function getDependencies(NodeInterface $node, Context $context)
    {
        return [];
    }

    public function deleteExistingObject(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        ucms_search_index()->delete($node->getName());
    }

    public function updateNodeFromExisting(NodeInterface $node, Context $context)
    {
        $builder = new ArrayTreeBuilder();

        foreach ($builder->parseWithoutRoot($this->getExistingObject($node, $context)) as $child) {
            $node->addChild($child);
        }
    }

    public function synchronize(NodeInterface $node, Context $context, $dirtyAllowed = false)
    {
        $param = $node->getValue();
        if (!$param) {
            $context->logCritical(sprintf("%s: index definition is empty", $node->getPath()));
        }
        if (empty($param['mappings'])) {
            $context->logCritical(sprintf("%s: index definition has no mapping structure", $node->getPath()));
        }
        if (empty($param['name'])) {
            $context->log(sprintf("%s: index definition has no 'name' attribute (human name)", $node->getPath()));
            $humanName = $node->getName();
        } else {
            $humanName = $param['name'];
        }

        unset($param['name']);

        \Drupal::service('ucms_search.index_storage')->save($node->getName(), $humanName, $param);
    }

    public function canProcess(NodeInterface $node)
    {
        return $node instanceof IndexNode;
    }
}
