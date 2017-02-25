<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;

/**
 * Test both the alias manager and the alias cache lookup
 */
class PathAliasTest extends AbstractDrupalTest
{
    use AliasTestTrait;

    /**
     * Tests alias manager basic functionnality
     */
    public function testAliasManagerStorage()
    {
        $aliasManager     = $this->getAliasManager();
        $menuStorage      = $this->getMenuStorage();
        $menuItemStorage  = $this->getTreeManager()->getItemStorage();

        // Bacic path computation using menus testing
        // We create a first site, with 2 nodes and a menu
        $site1    = $this->createDrupalSite();
        $menu1    = $menuStorage->create(uniqid('test-alias-1'), ['site_id' => $site1->getId()]);
        $node1A   = $this->createNodeWithAlias('1-a');
        $node1B   = $this->createNodeWithAlias('1-b');
        $item1A   = $menuItemStorage->insert($menu1->getId(), $node1A->id(), 'node 1 a');
        $item1B   = $menuItemStorage->insertAsChild($item1A, $node1B->id(), 'node 1 a/b');
        $item1AB  = $menuItemStorage->insertAsChild($item1B, $node1A->id(), 'node 1 a/b/a');
        // And a second site, with 2 nodes in another menu
        $site2    = $this->createDrupalSite();
        $menu2    = $menuStorage->create(uniqid('test-alias-2'), ['site_id' => $site2->getId()]);
        $node2A   = $this->createNodeWithAlias('2-a');
        $node2C   = $this->createNodeWithAlias('2-c');
        $item2A   = $menuItemStorage->insert($menu2->getId(), $node2A->id(), 'node 2 a');
        $item2C   = $menuItemStorage->insertAsChild($item2A, $node2C->id(), 'node 2 a/c');

        // Node 1 A is root in menu in site 1
        $this->assertSame('1-a',      $aliasManager->getPathAlias($node1A->id(), $site1->getId()));
        // Node 1 A is not in a menu in site 2
        $this->assertSame('1-a',      $aliasManager->getPathAlias($node1A->id(), $site2->getId()));
        // Node 1 B has a path in site 1
        $this->assertSame('1-a/1-b',  $aliasManager->getPathAlias($node1B->id(), $site1->getId()));
        // Node 1 B is not in a menu in site 2
        $this->assertSame('1-b',      $aliasManager->getPathAlias($node1B->id(), $site2->getId()));
        // Node 2 A is not in a menu in site 1
        $this->assertSame('2-a',      $aliasManager->getPathAlias($node2A->id(), $site1->getId()));
        // Node 2 A is root in menu in site 2
        $this->assertSame('2-a',      $aliasManager->getPathAlias($node2A->id(), $site2->getId()));
        // Node 2 C is not in a menu in site 1
        $this->assertSame('2-c',      $aliasManager->getPathAlias($node2C->id(), $site1->getId()));
        // Node 2 C has a path in site 2
        $this->assertSame('2-a/2-c',  $aliasManager->getPathAlias($node2C->id(), $site2->getId()));

        // Now let's complexify a bit our test case, we keep the data and
        // create additional menus in site 1, additional nodes, and we must
        // observe that the behaviour is predictible
        $menu3  = $menuStorage->create(uniqid('test-alias-3'), ['site_id' => $site1->getId()]);
        $menuStorage->toggleMainStatus($menu3->getId());
        $item3A = $menuItemStorage->insert($menu3->getId(), $node2A->id(), 'node 3 b');
        $item3B = $menuItemStorage->insertAsChild($item3A, $node1A->id(), 'node 3 b/a');

        // If the algorith works, menu 3 is now the main menu on site 1
        // and this default path have changed
        $this->assertSame('2-a/1-a',  $aliasManager->getPathAlias($node1A->id(), $site1->getId()));
        $this->assertSame('2-a',      $aliasManager->getPathAlias($node2A->id(), $site1->getId()));

        // If we change the main menu, aliases should follow
        $menuStorage->toggleMainStatus($menu1->getId());
        $this->assertSame('1-a',      $aliasManager->getPathAlias($node1A->id(), $site1->getId()));
        $this->assertSame('1-a/1-b',  $aliasManager->getPathAlias($node1B->id(), $site1->getId()));

        // @todo
        // When a node is cloned into a site, the parent alias should vanish
        // and the newly created node must inherit from the parent one
    }

    /**
     * Test alias lookup cache
     */
    public function testAliasCacheLookup()
    {

    }
}
