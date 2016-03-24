<?php

namespace MakinaCorpus\Ucms\Seo\Tests;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\node\Node;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Umenu\DrupalMenuStorage;

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
    protected $nodes = [];

    protected $menuName;

    protected $menuLinks = [];

    /**
     * @return NodeInterface
     */
    protected function createNode($alias, $published = true)
    {
        $node = new Node();
        $node->type = 'article';
        $node->setPublished($published);

        $this
            ->getEntityManager()
            ->getStorage('node')
            ->save($node)
        ;

        $this
            ->getDatabaseConnection()
            ->insert('ucms_seo_node')
            ->fields(['nid' => $node->id(), 'alias_segment' => $alias])
            ->execute()
        ;

        $this->nodes[$alias] = $node;
    }

    /**
     * @return SeoService
     */
    protected function getSeoService()
    {
        return $this->getDrupalContainer()->get('ucms_seo.seo_service');
    }

    /**
     * @return DrupalMenuStorage
     */
    protected function getMenuStorage()
    {
        return $this->getDrupalContainer()->get('umenu.storage');
    }

    /**
     * @return AliasStorageInterface
     */
    protected function getAliasStorage()
    {
        return $this->getDrupalContainer()->get('path.alias_storage');
    }

    protected function setUp()
    {
        parent::setUp();

        // Force menu module to be present for a few API functions
        require_once DRUPAL_ROOT . '/modules/menu/menu.module';

        // Creates a few nodes
        $this->createNode('foo');
        $this->createNode('bar');
        $this->createNode('baz');
        $this->createNode('john');
        $this->createNode('smith');

        // And now create the associated menu
        $this->menuName = uniqid('phpunit-seo-');
        $this->getMenuStorage()->create($this->menuName, [
            'title'       => $this->menuName,
            'description' => $this->menuName,
        ]);

        $item = [
            'link_path'   => 'node/' . $this->nodes['foo']->id(),
            'link_title'  => 'foo',
            'menu_name'   => $this->menuName,
        ];
        $this->menuLinks['foo'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->nodes['bar']->id(),
            'link_title'  => 'bar',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo'],
        ];
        $this->menuLinks['foo/bar'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->nodes['baz']->id(),
            'link_title'  => 'baz',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo/bar'],
        ];
        $this->menuLinks['foo/bar/baz'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->nodes['john']->id(),
            'link_title'  => 'john',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo'],
        ];
        $this->menuLinks['foo/john'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->nodes['smith']->id(),
            'link_title'  => 'smith',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo/john'],
        ];
        $this->menuLinks['foo/john/smith'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->nodes['foo']->id(),
            'link_title'  => 'foo',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo/john/smith'],
        ];
        $this->menuLinks['foo/john/smith/foo'] = menu_link_save($item);

        $item = [
            'link_path'   => 'node/' . $this->nodes['baz']->id(),
            'link_title'  => 'baz',
            'menu_name'   => $this->menuName,
            'plid'        => $this->menuLinks['foo'],
        ];
        $this->menuLinks['foo/baz'] = menu_link_save($item);
    }

    protected function tearDown()
    {
        $this
            ->getEntityManager()
            ->getStorage('node')
            ->delete($this->nodes)
        ;

        $this->getMenuStorage()->delete($this->menuName);
    }

    public function testMenuLinkAliasBuild()
    {
        $service = $this->getSeoService();

        foreach ($this->menuLinks as $path => $id) {
            $this->assertSame($path, $service->getLinkAlias($id));
        }
    }

    public function testMenuChildrenAliasBuild()
    {
        $links = $this
            ->getSeoService()
            ->getLinkChildrenAliases(
                $this->menuLinks['foo']
            )
        ;

        $fooLinks = $links[$this->nodes['foo']->id()];
        $barLinks = $links[$this->nodes['bar']->id()];
        $bazLinks = $links[$this->nodes['baz']->id()];
        $johnLinks = $links[$this->nodes['john']->id()];
        $smithLinks = $links[$this->nodes['smith']->id()];

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
        $node     = $this->nodes['john'];

        // Ensure aliases are in database (setUp() does not do it)
        $service->onAliasChange($this->nodes['foo']);

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

        $fooLinks = $links[$this->nodes['foo']->id()];
        $barLinks = $links[$this->nodes['bar']->id()];
        $bazLinks = $links[$this->nodes['baz']->id()];
        $johnLinks = $links[$this->nodes['john']->id()];
        $smithLinks = $links[$this->nodes['smith']->id()];

        $this->assertCount(2, $fooLinks);
        $this->assertCount(1, $barLinks);
        $this->assertCount(2, $bazLinks);
        $this->assertCount(1, $johnLinks);
        $this->assertCount(1, $smithLinks);

        // @todo later use expires too

        $this->assertContains('foo', $fooLinks);
        $this->assertContains('foo/roger/smith/foo', $fooLinks); // New
        $this->assertTrue($storage->aliasExists(  // Outdated
            'foo/john/smith/foo',
            LanguageInterface::LANGCODE_NOT_SPECIFIED),
            'node/' . $this->nodes['foo']->id()
        );
        $this->assertContains('foo/bar', $barLinks);
        $this->assertContains('foo/bar/baz', $bazLinks);
        $this->assertContains('foo/baz', $bazLinks);
        $this->assertContains('foo/roger', $johnLinks); // New
        $this->assertTrue($storage->aliasExists(  // Outdated
            'foo/john',
            LanguageInterface::LANGCODE_NOT_SPECIFIED),
            'node/' . $this->nodes['john']->id()
        );
        $this->assertContains('foo/roger/smith', $smithLinks); // New
        $this->assertTrue($storage->aliasExists(  // Outdated
            'foo/john/smith',
            LanguageInterface::LANGCODE_NOT_SPECIFIED),
            'node/' . $this->nodes['smith']->id()
        );
    }
}
