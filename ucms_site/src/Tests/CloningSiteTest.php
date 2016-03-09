<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\node\Node;
use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\NodeDispatcher;
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

    private $nidSeq = 0;


    protected function setUp()
    {
        parent::setUp();

        // Create a fully function template site
        $this->sites['template'] = $this->createDrupalSite(SiteState::ON, true);

        // Create some content on it
        $this->nodes['ref_homepage'] = $this->createDrupalNode('homepage', 'template');
        $this->nodes['ref_news'] = $this->createDrupalNode('news', 'template');

        // Create a pending site
        $this->sites['pending'] = $this->createDrupalSite(
            SiteState::PENDING,
            $this->sites['template']->getId()
        );
    }

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

    public function testCloningSite()
    {
        $nodeDispatcher = $this->getNodeDispatcher();
        $nodeDispatcher->cloneSite($this->sites['template'], $this->sites['pending']);

        // The 2 nodes from source should be referenced in target
        $nids = $this
            ->getDb()
            ->select('ucms_site_node', 'n')
            ->fields('n')
            ->condition('site_id', $this->sites['pending']->getId())
            ->execute()
            ->fetchCol();
        $this->assertCount(2, $nids);

        // We should have nodes grants in node_access table, for viewing in admin.
        $grants = $this
            ->getDb()
            ->select('ucms_site_node', 'n')
            ->fields('n')
            ->condition('site_id', $this->sites['pending']->getId())
            ->execute()
            ->fetchCol();
        $this->assertCount(2, $grants);
    }

    /**
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->getDrupalContainer()->get('ucms_site.manager');
    }

    /**
     * @return NodeDispatcher
     */
    private function getNodeDispatcher()
    {
        return $this->getDrupalContainer()->get('ucms_site.node_dispatcher');
    }

    /**
     * @return \DatabaseConnection
     */
    private function getDb()
    {
        return $this->getDrupalContainer()->get('database');
    }

    /**
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


}
