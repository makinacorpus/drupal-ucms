<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Seo\Tests\AliasTestTrait;
use MakinaCorpus\Ucms\Site\Site;

class CloningNodeTest extends AbstractDrupalTest
{
    use AliasTestTrait;

    protected function tearDown()
    {
        $this->eraseAllData();

        parent::tearDown();
    }

    protected function isNodeInLayout(NodeInterface $node, NodeInterface $inLayoutNode, Site $site)
    {
        return (bool)$this
            ->getDatabaseConnection()
            ->query(
                "
                    SELECT 1
                    FROM {ucms_layout} l
                    JOIN {ucms_layout_data} d ON d.layout_id = l.id
                    WHERE
                        l.nid = :lid
                        AND d.nid = :nid
                        AND l.site_id = :sid
                ",
                [
                    ':lid' => $node->id(),
                    ':nid' => $inLayoutNode->id(),
                    ':sid' => $site->getId(),
                ]
            )
            ->fetchField()
        ;
    }

    /**
     * @param NodeInterface $node
     * @param Site $site
     *
     * @return mixed[]
     *   First value is the Layout instance, second is an array of NodeInstance
     */
    protected function createArbitraryCompoForNodeInSite(NodeInterface $node, Site $site)
    {
        // Go for a composition on the node
        // Create some nodes
        $compo = [];
        $compo[] = $this->createDrupalNode('news');
        $compo[] = $this->createDrupalNode('news');
        $compo[] = $this->createDrupalNode('news');
        $layout = new Layout();
        $layout->setSiteId($site->getId());
        $layout->setNodeId($node->id());
        foreach ($compo as $irrevelantNode) {
            $layout->getRegion('content')->addAt(new Item($irrevelantNode->id()));
        }
        $this->getNodeManager()->createReference($site, $node);
        $this->getLayoutStorage()->save($layout);

        return [$layout, $compo];
    }

    public function testBasicNodeClone()
    {
        $site1 = $this->createDrupalSite();
        $site2 = $this->createDrupalSite();

        // Create a node, ensure it's in the site
        $node = $this->createDrupalNode('news', $site1->name);
        $this->assertSame($node->site_id, $site1->getId());
        $this->assertNodeInSite($node, $site1);

        // Create a reference, ensure it's OK
        $this->getNodeManager()->createReference($site2, $node);
        $this->assertTrue(isset($node->ucms_sites));
        $this->assertSame($node->site_id, $site1->getId());
        $this->assertContains($site2->getId(), $node->ucms_sites);
        $this->assertNodeInSite($node, $site2);

        // And now, go go go !!
        $clone = $this->getNodeManager()->createAndSaveClone($node);
        $this->assertNotEquals($node->id(), $clone->id());
        $this->assertSame($node->getTitle(), $clone->getTitle());

        // Various hardcoded overrides.
        $this->assertNotEquals($node->vid, $clone->vid);
        $this->assertNotSame($node->tnid, $clone->tnid);
        $this->assertSame(0, $clone->status);
        $this->assertSame(0, $clone->promote);
        $this->assertSame(0, $clone->sticky);

        // Parenting
        $this->assertSame($node->nid, $clone->parent_nid);
        $this->assertSame($node->nid, $clone->origin_nid);
        $cloneception = $this->getNodeManager()->createAndSaveClone($clone);
        $this->assertSame($clone->nid, $cloneception->parent_nid);
        $this->assertSame($node->nid, $cloneception->origin_nid);
    }

    public function testContextIsChanged()
    {
        $site1 = $this->createDrupalSite();
        $site2 = $this->createDrupalSite();

        $this->getSiteManager()->setContext($site1);
        $node = $this->createDrupalNode('news', $site1);
        $this->assertNodeInSite($node, $site1);

        $this->getSiteManager()->setContext($site2);
        $clone = $this->getNodeManager()->createAndSaveClone($node);
        // We got a clone, node should not be in site2 anymore (dereferenced)
        $this->assertNotNodeInSite($node, $site2);

        $this->assertEquals($site2->getId(), $clone->site_id);
        $this->assertNotNodeInSite($clone, $site1);
    }

    public function testLayoutIsChanged()
    {
        $site1 = $this->createDrupalSite();
        $site2 = $this->createDrupalSite();

        $this->getSiteManager()->setContext($site1);
        $node = $this->createDrupalNode('news', $site1);
        $this->assertNodeInSite($node, $site1);

        /** @var $compo NodeInterface[] */
        list(, $compo) = $this->createArbitraryCompoForNodeInSite($node, $site2);
        // I got a layout! I got a layout!
        foreach ($compo as $irrevelantNode) {
            $this->assertTrue($this->isNodeInLayout($node, $irrevelantNode, $site2));
            $this->assertFalse($this->isNodeInLayout($node, $irrevelantNode, $site1));
        }

        $this->getSiteManager()->setContext($site2);
        $clone = $this->getNodeManager()->createAndSaveClone($node);
        // We got a clone, node should not be in site2 anymore (dereferenced)
        $this->assertNotNodeInSite($node, $site2);

        $this->assertEquals($site2->getId(), $clone->site_id);
        $this->assertNotNodeInSite($clone, $site1);

        // I got a layout! I got a layout!
        foreach ($compo as $irrevelantNode) {
            // Layout changed for the clone node and is on site2
            $this->assertTrue($this->isNodeInLayout($clone, $irrevelantNode, $site2));
            // Layout never has been on site1
            $this->assertFalse($this->isNodeInLayout($clone, $irrevelantNode, $site1));
            // Original node has no layout anymore on site2
            $this->assertFalse($this->isNodeInLayout($node, $irrevelantNode, $site2));
        }
    }

    public function testOtherLayoutsAreChanged()
    {
        $site1 = $this->createDrupalSite();
        $site2 = $this->createDrupalSite();

        $this->getSiteManager()->setContext($site1);
        $node = $this->createDrupalNode('news', $site1);
        $this->getNodeManager()->createReference($site2, $node);

        $s1nodes = [];
        for ($i = 0; $i < 3; ++$i) {
            $s1nodes[] = $this->createDrupalNode('news', $site1);
        }
        $s2nodes = [];
        for ($i = 0; $i < 3; ++$i) {
            $s2nodes[] = $this->createDrupalNode('news', $site2);
        }

        /** @var $layout Layout */
        // Create a few layouts for site 1
        foreach ($s1nodes as $irrevelantNode) {
            list($layout) = $this->createArbitraryCompoForNodeInSite($irrevelantNode, $site1);
            // Add it twice, just to be sure
            $layout->getRegion('content')->append(new Item($node->id()));
            $layout->getRegion('r-' . rand(0, 5))->append(new Item($node->id()));
            $this->getLayoutStorage()->save($layout);
        }
        // Then for site 2
        foreach ($s2nodes as $irrevelantNode) {
            list($layout) = $this->createArbitraryCompoForNodeInSite($irrevelantNode, $site2);
            // Add it twice, just to be sure
            $layout->getRegion('content')->append(new Item($node->id()));
            $layout->getRegion('r-' . rand(0, 5))->append(new Item($node->id()));
            $this->getLayoutStorage()->save($layout);
        }

        // Assert our node is in all compositions we just created
        foreach ($s1nodes as $irrevelantNode) {
            $this->assertTrue($this->isNodeInLayout($irrevelantNode, $node, $site1));
            $this->assertFalse($this->isNodeInLayout($irrevelantNode, $node, $site2));
        }
        foreach ($s2nodes as $irrevelantNode) {
            $this->assertTrue($this->isNodeInLayout($irrevelantNode, $node, $site2));
            $this->assertFalse($this->isNodeInLayout($irrevelantNode, $node, $site1));
        }

        $this->getSiteManager()->setContext($site2);
        $clone = $this->getNodeManager()->createAndSaveClone($node);

        // Ok now assert that in site1, all references are kept
        foreach ($s1nodes as $irrevelantNode) {
            $this->assertTrue($this->isNodeInLayout($irrevelantNode, $node, $site1));
            $this->assertFalse($this->isNodeInLayout($irrevelantNode, $clone, $site1));
        }
        // And by the way, in site2 everything should have changed
        foreach ($s2nodes as $irrevelantNode) {
            $this->assertFalse($this->isNodeInLayout($irrevelantNode, $node, $site2));
            $this->assertTrue($this->isNodeInLayout($irrevelantNode, $clone, $site2));
        }
    }

    public function testAliasesAreGivenToNewNode()
    {
        $site1 = $this->createDrupalSite();
        $site2 = $this->createDrupalSite();

        $this->getSiteManager()->setContext($site1);
        $node = $this->createDrupalNode('news', $site1);
        $this->getSeoService()->setNodeSegment($node, 'foo_bar_site1');
        // Normal status
        $this->assertAliasExists('foo_bar_site1', $node, $site1);
        $this->assertNotAliasExists('foo_bar_site1', $node, $site2);

        $this->getNodeManager()->createReference($site2, $node);
        // Now everybody exists, yeah
        $this->assertAliasExists('foo_bar_site1', $node, $site1);
        $this->assertAliasExists('foo_bar_site1', $node, $site2);

        $this->getSiteManager()->setContext($site2);
        $clone = $this->getNodeManager()->createAndSaveClone($node);
        // Now, clone has site2 alias, parent does not have anymore
        $this->assertAliasExists('foo_bar_site1', $node, $site1);
        $this->assertNotAliasExists('foo_bar_site1', $node, $site2);
        $this->assertNotAliasExists('foo_bar_site1', $clone, $site1);
        $this->assertAliasExists('foo_bar_site1', $clone, $site2);
    }
}
