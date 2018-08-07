<?php

namespace MakinaCorpus\Ucms\Layout\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use Symfony\Component\HttpFoundation\Request;

class LayoutCacheFixesTest extends AbstractDrupalTest
{
    use SiteTestTrait;

    /**
     * This actually check for a but that might not even exists anymore, but
     * the test still is interesting to keep since it will ensure some of this
     * module features
     */
    public function testCachedResultAreWipedOutAtRead()
    {
        if (!$this->moduleExists('ucms_layout')) {
            $this->markTestSkipped("'ucms_layout' module is disabled");
        }

        // Create one site, which will contain one node, the future reference
        $site1 = $this->createDrupalSite();
        $bugCacheNode = $this->createDrupalNode('news', $site1, ['status' => 1]);

        // Then another that will contain our layout
        $site2 = $this->createDrupalSite();
        $layoutNode = $this->createDrupalNode('news', $site2, ['status' => 1]);
        $otherNode = $this->createDrupalNode('news', $site2, ['status' => 1]);

        // Enforce composition for this node to contain another node we will
        // ensure that this node still exists after cache cleanup
        $layout = new Layout();
        $layout->setSiteId($site2->getId());
        $layout->setNodeId($layoutNode->id());
        $layout->getRegion('content')->addAt(new Item($otherNode->id()));

        // We still don't have any assert to do here, we mostly check for the
        // backend not throwing SQL exceptions, so if we got here, we are happy
        $this->getLayoutStorage()->save($layout);

        // Now, add the other site's node: wwe do have a first but that this
        // assert checks: when saving a layout all nodes within should be
        // automatically added as site references
        $layout->getRegion('content')->addAt(new Item($bugCacheNode->id()));
        $this->getLayoutStorage()->save($layout);

        // WHAT WHAT not doing the right thing...
        // @todo
        //  - drop the item manually with SQL
        //  - check when loading the cached item, it is actually NOT in region
        //  - save it just like that
        //  - check that it still not in the region after load
    }

    /**
     * When cloning, referencing, doing that in many different ways, sometime
     * the node identifier gets lost and is not rightly propagated, thus layouts
     * are not being cloned as they should be.
     */
    public function testClonedLayoutGetsTheRightNodeId()
    {
        if (!$this->moduleExists('ucms_layout')) {
            $this->markTestSkipped("'ucms_layout' module is disabled");
        }

        // Create one site, which will contain one node, the future reference
        $site1 = $this->createDrupalSite();
        $bugNode = $this->createDrupalNode('news', $site1, ['status' => 1]);

        // Create node layout, with nothing in it.
        $layout = new Layout();
        $layout->setSiteId($site1->getId());
        $layout->setNodeId($bugNode->id());
        $this->getLayoutStorage()->save($layout);

        // We need another site, in which the node will be referenced, this
        // means its layout been cloned
        $site2 = $this->createDrupalSite();
        $this->getNodeManager()->createReference($site2, $bugNode);

        $clonedLayout = $this->getLayoutStorage()->findForNodeOnSite($bugNode->id(), $site2->getId());

        // Assert that the cloned layout is a new layout, it should have been
        // fully duplicated
        $this->assertNotEquals($layout->getId(), $clonedLayout->getId());
        $this->assertEquals($site2->getId(), $clonedLayout->getSiteId());
        $this->assertEquals($bugNode->id(), $clonedLayout->getNodeId());

        // Clone the node within the site, which will run all associated events
        // etc, which will actually change the layout data in database in order
        // to reference the new node (the clone)
        $this->getSiteManager()->setContext($site2, new Request());
        $clonedNode = $this->getNodeManager()->createAndSaveClone($bugNode);

        // Cloned layout are not statically cached, so reloading it is enough
        $clonedReloadedLayout = $this->getLayoutStorage()->load($clonedLayout->getId());
        // Just ensure we did load a new instance
        $this->assertNotSame($clonedLayout, $clonedReloadedLayout);

        // Ok, now just ensure the layout has the right node identifier
        $this->assertEquals($clonedNode->id(), $clonedReloadedLayout->getNodeId());
    }
}
