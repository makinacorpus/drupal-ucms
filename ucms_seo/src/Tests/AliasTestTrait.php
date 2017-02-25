<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use Drupal\Core\Path\AliasStorageInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Seo\Path\AliasCacheLookup;
use MakinaCorpus\Ucms\Seo\Path\AliasManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use MakinaCorpus\Umenu\MenuStorage;

trait AliasTestTrait
{
    use SiteTestTrait;

    /**
     * @return MenuStorage
     */
    protected function getMenuStorage()
    {
        return $this->getDrupalContainer()->get('umenu.menu_storage');
    }

    /**
     * @return AliasManager
     */
    protected function getAliasManager()
    {
        return $this->getDrupalContainer()->get('ucms_seo.alias_manager');
    }

    /**
     * @return AliasCacheLookup
     */
    protected function getAliasCacheLookup()
    {
        return $this->getDrupalContainer()->get('ucms_seo.alias_cache_lookup');
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
}
