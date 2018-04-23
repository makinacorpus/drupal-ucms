<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\SiteState;

class SeoBugfixesTest extends AbstractDrupalTest
{
    use AliasTestTrait;

    private $menuName;

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->getMenuStorage()->delete($this->menuName);
        $this->eraseAllData();

        parent::tearDown();
    }

    /**
     * Bugfix #536
     */
    public function testMenuRebuildAvoidsPrimaryKeyViolation()
    {
        $this->markTestSkipped("Please rewrite me!");

        // First, create a duplicate alias, we need a node, and check the alias exists.
        $site = $this->createDrupalSite(SiteState::ON, null, 'some_name');
        $node = $this->createNodeWithAlias('some_alias', 'article', $site);
        $this->assertAliasExists('some_alias', $node, $site);

        // Now add another node to the same site abitrary menu and prey
        $this->menuName = uniqid('phpunit-seo-');
        $this->getMenuStorage()->create($this->menuName, [
            'title'       => $this->menuName,
            'description' => $this->menuName,
            'site_id'     => $site->getId(),
        ]);

        // Alias is duplicate, and should not appear in database
        $otherNode = $this->createNodeWithAlias('some_alias', 'article', $site);
        $this->assertAliasExists('some_alias', $node, $site);
        $this->assertNotAliasExists('some_alias', $otherNode, $site);

        // Set in menu, until now, eveything is ok
        $item = [
            'link_path'   => 'node/' . $otherNode->id(),
            'link_title'  => 'foo',
            'menu_name'   => $this->menuName,
        ];
        $menuLinkId = menu_link_save($item);
        $item = [
            'link_path'   => 'node/' . $node->id(),
            'link_title'  => 'foo',
            'menu_name'   => $this->menuName,
            'plid'        => $menuLinkId,
        ];
        menu_link_save($item);
        // Trigger the onAliasChange() behavior, it should fail
        $this->getSeoService()->onAliasChange($otherNode, $this->menuName);
    }
}
