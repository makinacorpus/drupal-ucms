<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\node\Node;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Layout\DrupalStorage;
use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MakinaCorpus\Ucms\Seo\SeoService;

trait SiteBasedTestTrait
{
    /**
     * @var Site[]
     */
    protected $sites;

    /**
     * @var Node[]
     */
    protected $nodes;

    /**
     * @var integer
     */
    protected $nidSeq = 0;

    /**
     * @return ContainerInterface
     */
    abstract protected function getDrupalContainer();

    /**
     * @return SiteManager
     */
    protected function getSiteManager()
    {
        return $this->getDrupalContainer()->get('ucms_site.manager');
    }

    /**
     * @return NodeManager
     */
    protected function getNodeManager()
    {
      return $this->getDrupalContainer()->get('ucms_site.node_manager');
    }

    /**
     * @return SeoService
     */
    protected function getSeoService()
    {
        return $this->getDrupalContainer()->get('ucms_seo.seo_service');
    }

    /**
     * @return DrupalStorage
     */
    protected function getLayoutStorage()
    {
        return $this->getDrupalContainer()->get('ucms_layout.storage');
    }

    protected function isNodeInSite($node, $site)
    {
        $site = (int)($site instanceof Site) ? $site->getId() : $site;
        $node = (int)($node instanceof NodeInterface) ? $node->id() : $node;

        return (bool)$this->getDatabaseConnection()->query("SELECT 1 FROM {ucms_site_node} WHERE nid = :nid AND site_id = :sid", [':nid' => $node, ':sid' => $site])->fetchField();
    }

    /**
     * Assert that given node is in site, in database
     */
    protected function assertNotNodeInSite($node, $site)
    {
        $this->assertFalse($this->isNodeInSite($node, $site));
    }

    /**
     * Assert that given node is in site, in database
     */
    protected function assertNodeInSite($node, $site)
    {
        $this->assertTrue($this->isNodeInSite($node, $site));
    }

    /**
     * Create a site with template context.
     *
     * @param integer $state
     *   Site state
     * @param boolean|integer $template
     *   true means this is a template, integer means the template site identifier
     * @param string $name
     *   Arbitrary name for site
     *
     * @return Site
     */
    protected function createDrupalSite($state = SiteState::ON, $template = null, $name = null)
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


        if (!$name) {
            $name = $stupidHash;
        }
        $site->name = $name;

        return $this->sites[$name] = $site;
    }

    /**
     * Get previously created site during this test
     *
     * @param string $name
     *
     * @return Site
     */
    protected function getSite($name)
    {
        if (!isset($this->sites[$name])) {
            throw new \InvalidArgumentException(sprintf("did you forgot to create the site '%s'? ", $name));
        }
        return $this->sites[$name];
    }

    /**
     * Create a node on a site.
     *
     * @param string $type
     * @param string $site
     *
     * @return NodeInterface
     */
    protected function createDrupalNode($type, $site = null)
    {
        $node = new Node();
        $node->title = 'Node test '.$this->nidSeq++;;
        $node->status = NODE_PUBLISHED;
        $node->is_global = false;
        $node->type = $type;
        $node->is_group = false;
        $node->is_clonable = false;

        if ($site instanceof Site) {
            $node->site_id = $site->getId();
        } else if (is_string($site)) {
            $node->site_id = $this->getSite($site)->getId();
        } else {
            $node->site_id = null;
        }
        node_save($node);

        return $this->nodes[] = $node;
    }

    protected function eraseAllData()
    {
        foreach ($this->sites as $site) {
            $this->getSiteManager()->getStorage()->delete($site);
        }
        foreach ($this->nodes as $node) {
            node_delete($node->nid);
        }

        $this->getSiteManager()->dropContext();
    }
}
