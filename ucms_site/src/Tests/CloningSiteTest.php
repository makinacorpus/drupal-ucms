<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\node\Node;
use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Layout\DrupalStorage;
use MakinaCorpus\Ucms\Layout\Item;
use MakinaCorpus\Ucms\Layout\Layout;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class CloningSiteTest extends AbstractDrupalTest
{
    /**
     * @var Site[]
     */
    private $sites;

    /**
     * @var Node[]
     */
    private $nodes;

    /**
     * @var Layout
     */
    private $layout;

    private $nidSeq = 0;


    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->getDrupalContainer()->get('ucms_site.manager');
    }

    /**
     * @return \DatabaseConnection
     */
    private function getDb()
    {
        return $this->getDrupalContainer()->get('database');
    }

    /**
     * @return DrupalStorage
     */
    private function getLayoutStorage()
    {
        return $this->getDrupalContainer()->get('ucms_layout.storage');
    }

    /**
     * Create a site with template context.
     *
     * @param $state
     * @param $template
     * @return Site
     */
    protected function createDrupalSite($state, $template)
    {
        $site = new Site();
        $stupidHash = uniqid().mt_rand();
        $site->state = (int)$state;
        $site->title = $stupidHash;
        $site->title_admin = $stupidHash;
        $site->http_host = $stupidHash.'.example.com';

        if ($template === true) {
            // this site is a template
            $site->is_template = 1;
            $site->template_id = 0;
        } else {
            // this site is created from a template
            $site->is_template = 0;
            $site->template_id = $template;
        }
        $this->getSiteManager()->getStorage()->save($site);

        return $site;
    }

    /**
     * Create a node on a site.
     *
     * @param string $type
     * @param string $site
     * @return Node
     */
    private function createDrupalNode($type, $site)
    {
        $node = new Node();
        $node->title = 'Node test '.$this->nidSeq++;;
        $node->status = NODE_PUBLISHED;
        $node->is_global = false;
        $node->type = $type;
        $node->is_group = false;
        $node->is_clonable = false;
        $node->site_id = $this->sites[$site]->getId();
        node_save($node);

        return $node;
    }

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

    protected function tearDown()
    {
        foreach ($this->sites as $site) {
            $this->getSiteManager()->getStorage()->delete($site);
        }
        foreach ($this->nodes as $node) {
            node_delete($node->nid);
        }

        $this->getSiteManager()->dropContext();

        parent::tearDown();
    }

    /**
     * @param $template
     * @param $pending
     */
    protected function assertAllTheThings($template, $pending)
    {
        // The 2 nodes from source should be referenced in target
        $nids = $this
            ->getDb()
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
            ->getDb()
            ->select('node_access', 'n')
            ->condition('gid', $pending->getId())
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(6, $grants);

        // We should have layout
        $layout_ids = $this
            ->getDb()
            ->select('ucms_layout', 'u')
            ->fields('u', ['id'])
            ->condition('site_id', $pending->getId())
            ->execute()
            ->fetchCol()
        ;
        $this->assertEquals(1, count($layout_ids));

        // And layout data
        $layout_data = $this
            ->getDb()
            ->select('ucms_layout_data', 'u')
            ->condition('layout_id', $layout_ids)
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(1, $layout_data);

        // And menu links too
        $links = $this
            ->getDb()
            ->select('menu_links', 'm')
            ->condition('menu_name', 'site-main-'.$pending->getId())
            ->countQuery()
            ->execute()
            ->fetchField()
        ;
        $this->assertEquals(2, $links);
    }
}
