<?php

namespace MakinaCorpus\Ucms\Seo\StoreLocator;


use Drupal\node\NodeInterface;

interface StoreLocatorInterface
{

    public function getTitle();

    public function getMapItems();

    public function getLinks();

    /**
     * @param \Drupal\node\NodeInterface|null $childNode
     *   A node to update the alias. If none given, apply to all nodes.
     */
    public function rebuildAliases(NodeInterface $childNode = null);
}
