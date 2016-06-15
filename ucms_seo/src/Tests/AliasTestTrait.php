<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use Drupal\Core\Path\AliasStorageInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use MakinaCorpus\Umenu\DrupalMenuStorage;

trait AliasTestTrait
{
    use SiteTestTrait;

    /**
     * @return DrupalMenuStorage
     */
    protected function getMenuStorage()
    {
        return $this->getDrupalContainer()->get('umenu.menu_storage');
    }

    /**
     * @return AliasStorageInterface
     */
    protected function getAliasStorage()
    {
        return $this->getDrupalContainer()->get('path.alias_storage');
    }

    /**
     * @return NodeInterface
     */
    protected function createNodeWithAlias($alias, $type = 'article', $site = null, $values = [])
    {
        $values['ucms_seo_segment'] = $alias;

        $node = $this->createDrupalNode($type, $site, $values);

        return $node;
    }

    protected function doesAliasExists($alias, $nodeId = null, $siteId = null)
    {
        if ($siteId instanceof Site) {
            $siteId = $siteId->getId();
        }
        if ($nodeId instanceof NodeInterface) {
            $nodeId = $nodeId->id();
        }

        return (bool)$this
            ->getDatabaseConnection()
            ->query(
                "SELECT 1 FROM {ucms_seo_alias} WHERE site_id = :site AND node_id = :nid AND alias = :alias",
                [
                    ':site'   => $siteId,
                    ':nid'    => $nodeId,
                    ':alias'  => $alias,
                ]
            )
            ->fetchField()
        ;
    }

    protected function assertAliasExists($alias, $nodeId = null, $siteId = null)
    {
        $this->assertTrue($this->doesAliasExists($alias, $nodeId, $siteId));
    }

    protected function assertNotAliasExists($alias, $nodeId = null, $siteId = null)
    {
        $this->assertFalse($this->doesAliasExists($alias, $nodeId, $siteId));
    }
}
