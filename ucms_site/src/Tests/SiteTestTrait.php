<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\Core\Entity\EntityManager;
use Drupal\node\Node;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Layout\DrupalStorage;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Testing in site basics
 */
trait SiteTestTrait
{
    /**
     * @var Site[]
     */
    protected $sites = [];

    /**
     * @var Node[]
     */
    protected $nodes = [];

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
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->getDrupalContainer()->get('entity.manager');
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
     * @return TreeManager
     */
    protected function getTreeManager()
    {
        return $this->getDrupalContainer()->get('umenu.manager');
    }

    /**
     * @return DrupalStorage
     */
    protected function getLayoutStorage()
    {
        return $this->getDrupalContainer()->get('ucms_layout.storage');
    }

    /**
     * Does node exists in site
     *
     * @param int|NodeInterface $node
     * @param int|Site $site
     *
     * @return bool
     */
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
    protected function createDrupalSite($state = SiteState::ON, $template = null, $name = null, $values = [])
    {
        $site = new Site();

        $stupidHash = uniqid().mt_rand();
        $site->state = (int)$state;
        $site->title = $stupidHash;
        $site->title_admin = $stupidHash;
        $site->http_host = $stupidHash.'.example.com';

        foreach ($values as $key => $value) {
            $site->{$key} = $value;
        }

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
     * @param mixed[] $values
     *
     * @return NodeInterface
     */
    protected function createDrupalNode($type = 'test', $site = null, $values = [])
    {
        $node = new Node();
        $node->title = 'Node test '.$this->nidSeq++;;
        $node->status = 1;
        $node->is_global = false;
        $node->type = $type;
        $node->is_corporate = false;
        $node->is_clonable = false;

        foreach ($values as $key => $value) {
            $node->{$key} = $value;
        }

        if ($site instanceof Site) {
            $node->site_id = $site->getId();
        } else if (is_string($site)) {
            $node->site_id = $this->getSite($site)->getId();
        } else {
            $node->site_id = null;
        }

        if ($site instanceof Site) {
            $this->getSiteManager()->getStorage()->save($site);
        }

        $this->getEntityManager()->getStorage('node')->save($node);

        return $this->nodes[] = $node;
    }

    /**
     * Add node into site
     *
     * @param string $name
     *   Site test name
     * @param int $nodeId
     *   Node identifier
     */
    protected function addNodeToSite($name, $nodeId)
    {
        $this->getNodeManager()->createReferenceBulkInSite($this->getSite($name)->getId(), [$nodeId]);
    }

    /**
     * Remove node from site
     *
     * @param string $name
     *   Site test name
     * @param int $nodeId
     *   Node identifier
     */
    protected function removeNodeFromSite($name, $nodeId)
    {
        $this->getNodeManager()->deleteReferenceBulkFromSite($this->getSite($name)->getId(), [$nodeId]);
    }

    /**
     * Call this in your tearDown()
     */
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
