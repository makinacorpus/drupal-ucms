<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Site\Site;

class CloningNodeTest extends AbstractDrupalTest
{
    use SiteBasedTestTrait;

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

        // Go for a composition on the node
        // Create some nodes
        $compo = [];
        $compo[] = $this->createDrupalNode('news');
        $compo[] = $this->createDrupalNode('news');
        $compo[] = $this->createDrupalNode('news');
        $layout = new Layout();
        $layout->setSiteId($site2->getId());
        $layout->setNodeId($node->id());
        foreach ($compo as $irrevelantNode) {
            $layout->getRegion('content')->addAt(new Item($irrevelantNode->id()));
        }
        $this->getLayoutStorage()->save($layout);

        // I got a layout! I got a layout!
        foreach ($compo as $irrevelantNode) {
            $this->assertTrue($this->isNodeInLayout($node, $irrevelantNode, $site2));
            $this->assertFalse($this->isNodeInLayout($node, $irrevelantNode, $site1));
        }

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
}
