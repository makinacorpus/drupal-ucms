<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\SiteState;
use Symfony\Component\HttpFoundation\Request;

/**
 * Now, how aliases are built: for example, lets say you have nodes,
 * with their respective aliases (identifiers don't matter):
 *
 *   node/1 -> foo
 *   node/2 -> bar
 *   node/3 -> baz
 *   node/4 -> john
 *   node/5 -> smith
 *
 * and the following main menu tree:
 *   - node/1
 *     - node/2
 *       - node/3
 *     - node/4
 *       - node/5
 *         - node/1 (this is a trap)
 *     - node/3 (this is a trap too)
 *
 * then you'll get the following path aliases:
 *
 *   node/1 -> foo AND foo/john/smith/foo (remember the first trap)
 *   node/2 -> foo/bar
 *   node/3 -> foo/bar/baz AND foo/baz (remember the second trap)
 *   node/4 -> foo/john
 *   node/5 -> foo/john/smith
 */
class SeoServiceTest extends AbstractDrupalTest
{
    use AliasTestTrait;

    protected $localNodes = [];
    protected $menuName;
    protected $menuLinks = [];

    protected function setUp()
    {
        parent::setUp();

        $this->markTestSkipped("Please rewrite me!");

        // Force menu module to be present for a few API functions
        require_once DRUPAL_ROOT . '/modules/menu/menu.module';

        // Creates a few nodes
        $this->localNodes['foo'] = $this->createNodeWithAlias('foo');
        $this->localNodes['bar'] = $this->createNodeWithAlias('bar');
        $this->localNodes['baz'] = $this->createNodeWithAlias('baz');
        $this->localNodes['john'] = $this->createNodeWithAlias('john');
        $this->localNodes['smith'] = $this->createNodeWithAlias('smith');

        // And now create the associated menu
        $this->menuName = uniqid('phpunit-seo-');
        $this->getMenuStorage()->create($this->menuName, [
            'title'       => $this->menuName,
            'description' => $this->menuName,
        ]);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['foo']->id(),
            'link_title'  => 'foo',
            'menu_name'   => $this->menuName,
        ];
        $this->menuLinks['foo'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['bar']->id(),
            'link_title'  => 'bar',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo'],
        ];
        $this->menuLinks['foo/bar'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['baz']->id(),
            'link_title'  => 'baz',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo/bar'],
        ];
        $this->menuLinks['foo/bar/baz'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['john']->id(),
            'link_title'  => 'john',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo'],
        ];
        $this->menuLinks['foo/john'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['smith']->id(),
            'link_title'  => 'smith',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo/john'],
        ];
        $this->menuLinks['foo/john/smith'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['foo']->id(),
            'link_title'  => 'foo',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo/john/smith'],
        ];
        $this->menuLinks['foo/john/smith/foo'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->localNodes['baz']->id(),
            'link_title'  => 'baz',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo'],
        ];
        $this->menuLinks['foo/baz'] = menu_link_save($item);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->getMenuStorage()->delete($this->menuName);
        $this->eraseAllData();

        parent::tearDown();
    }

    public function testMenuLinkAliasBuild()
    {
        $service = $this->getSeoService();

        foreach ($this->menuLinks as $path => $id) {
            $this->assertSame($path, $service->getLinkAlias($id));
        }
    }

    /**
     * Fixed a bug on orders of orders in the preload phase.
     */
    public function testMenuLinkPreloadAliasOrder()
    {
        $service = $this->getSeoService();

        // Let's create 4 aliases for the same node
        $site = $this->createDrupalSite(SiteState::ON, null, 'some_name');
        $this->getSiteManager()->setContext($site, new Request());
        $node = $this->createNodeWithAlias('some_alias', 'article', $site);
        $langcode = $node->language()->getId();
        $source = 'node/'.$node->id();

        // first has been created during node save, priority -100, lang und
        $firstAlias = $service->getAliasStorage()->load(['source' => $source]);

        // Second is priority 0, will be canonical, lang und
        $this->getAliasStorage()->save($source, 'foo');
        $secondAlias = $service->getAliasStorage()->load(['source' => $source]);

        // Third is priority 0, will not be canonical, lang und
        $this->getAliasStorage()->save($source, 'foo/bar');
        $thirdAlias = $service->getAliasStorage()->load(['source' => $source]);

        // Fourth is priority 100, will not be canonical, lang und
        $this->getAliasStorage()->save($source, 'foo/bar/baz');
        $fourthAlias = $service->getAliasStorage()->load(['source' => $source]);

        // Update node_id as there is no API for now
        db_update('ucms_seo_alias')
            ->fields(['node_id' => $node->id()])
            ->condition('source', $source)
            ->execute()
        ;

        // Set the second as canonical
        $service->setCanonicalForAlias($secondAlias);

        // Set the fourth with high priority
        db_update('ucms_seo_alias')
            ->fields(['priority' => 100])
            ->condition('pid', $fourthAlias['pid'])
            ->execute()
        ;

        // Now all queries should return the same alias, the conical one
        $getNodeCanonicalAlias = $service->getNodeCanonicalAlias($node);
        $preloadPathAlias = $service->getAliasStorage()->preloadPathAlias([$source], $langcode);
        $lookupPathAlias = $service->getAliasStorage()->lookupPathAlias($source, $langcode);
        $this->assertEquals('foo', $preloadPathAlias[$source]);
        $this->assertEquals('foo', $getNodeCanonicalAlias->alias);
        $this->assertEquals('foo', $lookupPathAlias);

        // TODO: test with langcodes too

        $this->getSiteManager()->dropContext();
    }

    public function testMenuChildrenAliasBuild()
    {
        $links = $this
            ->getSeoService()
            ->getLinkChildrenAliases(
                $this->menuLinks['foo']
            )
        ;

        $fooLinks   = $links[$this->localNodes['foo']->id()];
        $barLinks   = $links[$this->localNodes['bar']->id()];
        $bazLinks   = $links[$this->localNodes['baz']->id()];
        $johnLinks  = $links[$this->localNodes['john']->id()];
        $smithLinks = $links[$this->localNodes['smith']->id()];

        $this->assertCount(2, $fooLinks);
        $this->assertCount(1, $barLinks);
        $this->assertCount(2, $bazLinks);
        $this->assertCount(1, $johnLinks);
        $this->assertCount(1, $smithLinks);

        $this->assertContains('foo', $fooLinks);
        $this->assertContains('foo/john/smith/foo', $fooLinks);
        $this->assertContains('foo/bar', $barLinks);
        $this->assertContains('foo/bar/baz', $bazLinks);
        $this->assertContains('foo/baz', $bazLinks);
        $this->assertContains('foo/john', $johnLinks);
        $this->assertContains('foo/john/smith', $smithLinks);
    }

    public function testNodeMergeAliases()
    {
        $service  = $this->getSeoService();
        $storage  = $this->getAliasStorage();
        $node     = $this->localNodes['john'];

        // Ensure aliases are in database (setUp() does not do it)
        $service->onAliasChange($this->localNodes['foo']);

        // Change the alias, go go go!
        $this
            ->getDatabaseConnection()
            ->update('ucms_seo_node')
            ->condition('nid', $node->id())
            ->fields(['alias_segment' => 'roger'])
            ->execute()
        ;

        // And raise the event
        $service->onAliasChange($node);

        $links = $service->getLinkChildrenAliases($this->menuLinks['foo']);

        $fooLinks = $links[$this->localNodes['foo']->id()];
        $barLinks = $links[$this->localNodes['bar']->id()];
        $bazLinks = $links[$this->localNodes['baz']->id()];
        $johnLinks = $links[$this->localNodes['john']->id()];
        $smithLinks = $links[$this->localNodes['smith']->id()];

        $this->assertCount(2, $fooLinks);
        $this->assertCount(1, $barLinks);
        $this->assertCount(2, $bazLinks);
        $this->assertCount(1, $johnLinks);
        $this->assertCount(1, $smithLinks);

        // @todo later use expires too

        $this->assertContains('foo', $fooLinks);
        $this->assertContains('foo/roger/smith/foo', $fooLinks); // New
//         $this->assertTrue($storage->aliasExists(  // Outdated
//             'foo/john/smith/foo',
//             LanguageInterface::LANGCODE_NOT_SPECIFIED),
//             'node/' . $this->localNodes['foo']->id()
//         );
        $this->assertContains('foo/bar', $barLinks);
        $this->assertContains('foo/bar/baz', $bazLinks);
        $this->assertContains('foo/baz', $bazLinks);
        $this->assertContains('foo/roger', $johnLinks); // New
//         $this->assertTrue($storage->aliasExists(  // Outdated
//             'foo/john',
//             LanguageInterface::LANGCODE_NOT_SPECIFIED),
//             'node/' . $this->localNodes['john']->id()
//         );
        $this->assertContains('foo/roger/smith', $smithLinks); // New
//         $this->assertTrue($storage->aliasExists(  // Outdated
//             'foo/john/smith',
//             LanguageInterface::LANGCODE_NOT_SPECIFIED),
//             'node/' . $this->localNodes['smith']->id()
//         );
    }
}
