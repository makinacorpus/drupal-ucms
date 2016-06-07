<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\node\Node;
use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class CloningSiteTest extends AbstractDrupalTest
{
    use SiteBasedTestTrait;

    /**
     * @var Layout
     */
    private $layout;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        // Create a fully function template site
        $this->sites['template'] = $this->createDrupalSite(SiteState::ON, true);
        $this->sites['not_relevant'] = $this->createDrupalSite(SiteState::ON, true);

        // Create some content on it
        $this->nodes['ref_homepage'] = $this->createDrupalNode('homepage', 'template');
        $this->nodes['ref_news'] = $this->createDrupalNode('news', 'template');
        $this->nodes['not_relevant_homepage'] = $this->createDrupalNode('homepage', 'not_relevant');
        $this->nodes['not_relevant_news'] = $this->createDrupalNode('news', 'not_relevant');

        // Add some menu links
        $item = [
            'menu_name'  => 'site-main-'.$this->sites['template']->getId(),
            'link_path'  => 'node/'.$this->nodes['ref_homepage']->id(),
            'link_title' => 'node/'.$this->nodes['ref_homepage']->getTitle(),
        ];
        menu_link_save($item);
        $item = [
            'menu_name'  => 'site-main-'.$this->sites['template']->getId(),
            'link_path'  => 'node/'.$this->nodes['ref_news']->id(),
            'link_title' => 'node/'.$this->nodes['ref_news']->getTitle(),
        ];
        menu_link_save($item);
        $item = [
            'menu_name'  => 'site-main-'.$this->sites['not_relevant']->getId(),
            'link_path'  => 'node/'.$this->nodes['ref_news']->id(),
            'link_title' => 'node/'.$this->nodes['ref_news']->getTitle(),
        ];
        menu_link_save($item);

        // Create some layout on it
        $layout = new Layout();
        $layout->setNodeId($this->nodes['ref_homepage']->id());
        $layout->setSiteId($this->sites['template']->getId());

        // Compose something
        $layout->getRegion('content')->addAt(new Item($this->nodes['ref_news']->id()));
        $this->getLayoutStorage()->save($layout);

        $this->layout = $layout;

        $layout = new Layout();
        $layout->setNodeId($this->nodes['not_relevant_homepage']->id());
        $layout->setSiteId($this->sites['not_relevant']->getId());
        $layout->getRegion('content')->addAt(new Item($this->nodes['not_relevant_news']->id()));
        $this->getLayoutStorage()->save($layout);

        // Create a pending site
        $this->sites['pending'] = $this->createDrupalSite(
            SiteState::PENDING,
            $this->sites['template']->getId()
        );
        $this->sites['another'] = $this->createDrupalSite(
            SiteState::PENDING,
            $this->sites['template']->getId()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->eraseAllData();

        parent::tearDown();
    }

    public function testCloningSite()
    {
        $siteManager = $this->getSiteManager();
        $pending = $this->sites['pending'];
        $template = $this->sites['template'];

        $siteManager->getStorage()->duplicate($template, $pending);
        $this->assertAllTheThings($template, $pending);

        // Create another site
        $siteManager->getStorage()->duplicate($template, $this->sites['another']);
        $this->assertAllTheThings($template, $pending);
    }

    protected function assertAllTheThings(Site $template, Site $pending)
    {
        // The 2 nodes from source should be referenced in target
        $nids = $this
            ->getDatabaseConnection()
            ->select('ucms_site_node', 'n')
            ->condition('site_id', $pending->getId())
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(2, $nids);

        // Home should have been copied to site and referenced
        $this->assertEquals($template->getHomeNodeId(), $pending->getHomeNodeId());

        // We should have nodes grants in node_access table, for viewing in admin.
        $grants = $this
            ->getDatabaseConnection()
            ->select('node_access', 'n')
            ->condition('gid', $pending->getId())
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(6, $grants);

        // We should have layout
        $layout_ids = $this
            ->getDatabaseConnection()
            ->select('ucms_layout', 'u')
            ->fields('u', ['id'])
            ->condition('site_id', $pending->getId())
            ->execute()
            ->fetchCol()
        ;
        $this->assertEquals(1, count($layout_ids));

        // And layout data
        $layout_data = $this
            ->getDatabaseConnection()
            ->select('ucms_layout_data', 'u')
            ->condition('layout_id', $layout_ids)
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(1, $layout_data);

        // And menu links too
        $links = $this
            ->getDatabaseConnection()
            ->select('menu_links', 'm')
            ->condition('menu_name', 'site-main-'.$pending->getId())
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(2, $links);

        # @todo Ensure that nodes are marked for reindex
    }
}
