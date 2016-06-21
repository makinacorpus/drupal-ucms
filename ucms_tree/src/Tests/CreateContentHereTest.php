<?php

namespace MakinaCorpus\Ucms\Tree\Tests;

use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use MakinaCorpus\Umenu\TreeManager;

// @todo THIS IS SO WROING AND THIS SHOULD NTO BE TESTED BUT insertChildAt() AND insertAt() INSTEAD FOUUUQUE XWHAT DA PHOQUE
class CreateContentHereTest extends AbstractDrupalTest
{
    use SiteTestTrait;

    private $site;
    private $menuName;
    private $menuId;
    private $menuLinks;

    /**
     * @return TreeManager
     */
    protected function getTreeManager()
    {
        return $this->getDrupalContainer()->get('umenu.manager');
    }

    /**
     * @return int
     */
    protected function createMenuItem($name, $node, $parent = null)
    {
        $parentId = null;

        if ($parent) {
            if (empty($this->menuLinks[$parent])) {
                throw new \InvalidArgumentException("You are somehow stupid, and parent does not exist");
            }
            $parentId = $this->menuLinks[$parent];
        }

        if ($parentId) {
            $id = $this->getTreeManager()->getItemStorage()->insertAsChild($parentId, $node->id(), $name);
        } else {
            $id = $this->getTreeManager()->getItemStorage()->insert($this->menuId, $node->id(), $name);
        }

        return $this->menuLinks[$name] = $id;
    }

    protected function setUp()
    {
        parent::setUp();

        if (!$this->getDrupalContainer()->has('umenu.manager')) {
            $this->markTestSkipped();

            return; // Sorry.
        }

        $this->site = $this->createDrupalSite();

        $menu = $this->getTreeManager()->getMenuStorage()->create(uniqid('bouyaya-'), ['site_id' => $this->site->getId()]);

        $this->menuName = $menu['name'];
        $this->menuId = $menu['id'];

        /*
         * a
         * a/1
         * a/2
         * a/3
         * b
         * c
         */
        $this->createMenuItem('a',  $this->createDrupalNode(['status' => 1]));
        $this->createMenuItem('a1', $this->createDrupalNode(['status' => 1]), 'a');
        $this->createMenuItem('a2', $this->createDrupalNode(['status' => 1]), 'a');
        $this->createMenuItem('a3', $this->createDrupalNode(['status' => 1]), 'a');
        $this->createMenuItem('b',  $this->createDrupalNode(['status' => 1]));
        $this->createMenuItem('c',  $this->createDrupalNode(['status' => 1]));
    }

    protected function tearDown()
    {
        if ($this->menuName) {
            $this->getTreeManager()->getMenuStorage()->delete($this->menuName);
        }

        $this->eraseAllData();

        parent::tearDown();
    }

    /**
     * @return NodeInterface
     */
    protected function insertDrupalNodeInMenu($parent, $position = 0)
    {
        return $this->createDrupalNode('test', $this->site, ['menu' => [
            'menu'      => $this->menuId,
            'parent'    => $parent ? $this->menuLinks[$parent] : 0,
            'position'  => $position,
        ]]);
    }

    public function testAsRootPrepend()
    {
        $node = $this->insertDrupalNodeInMenu(null, 0); // Before 'a'

        $tree = $this->getTreeManager()->buildTree($this->menuId);
        foreach ($tree->getChildren() as $item) {
            $this->assertSame($node->getTitle(), $item->getTitle());
            // Item is the first
            break;
        }
    }

    public function testAsRootMiddle()
    {
        $node = $this->insertDrupalNodeInMenu(null, 2); // Before 'c'
    }

    public function testAsRootOutOfBounds()
    {
        $node = $this->insertDrupalNodeInMenu(null, 112); // After 'd'
    }

    public function testWithParentPrepend()
    {
        $node = $this->insertDrupalNodeInMenu(null, 0); // Before 'a1'

        $tree = $this->getTreeManager()->buildTree($this->menuId);
        foreach ($tree->getItemById($this->menuLinks['a'])->getChildren() as $item) {
            $this->assertSame($node->getTitle(), $item->getTitle());
            // Item is the first under 'a'
            break;
        }
    }

    public function testWithParentMiddle()
    {
        $node = $this->insertDrupalNodeInMenu(null, 1); // Before 'a2'
    }

    public function testWithParentOutOfBounds()
    {
        $node = $this->insertDrupalNodeInMenu(null, 42); // After 'a3'
    }
}
