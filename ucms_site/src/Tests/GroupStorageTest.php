<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Group;

class GroupStorageTest extends AbstractDrupalTest
{
    use GroupTestTrait;

    protected function setUp()
    {
        parent::setUp();
    }

    public function testBasicStorage()
    {
        $groupManager = $this->getGroupManager();

        try {
            $groupManager->findOne(PHP_INT_MAX); // should never happen in dev env
            $this->fail();
        } catch (\InvalidArgumentException $e) {
            // OK
        }

        // Assert that creation can be done pragmatically
        $group = new Group();
        $this->assertNull($group->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $group->createdAt());
        $this->assertInstanceOf(\DateTimeInterface::class, $group->changedAt());
        $this->assertEquals($group->createdAt(), $group->changedAt());

        // Set some title, we will need that
        $group->setTitle('foo_bar_bar');
        $this->assertSame('foo_bar_bar', $group->getTitle());

        // Save it, and prey for everything to have changed
        sleep(1); // Really sorry, but necessary
        $groupManager->save($group);

        // Check everything that should have been set is set
        $this->assertNotEmpty($group->getId());
        $this->assertTrue(is_int($group->getId()));
        $this->assertFalse($group->isGhost());
        $this->assertFalse($group->isMeta());
        $this->assertNotEquals($group->createdAt(), $group->changedAt());
        $this->assertGreaterThan($group->createdAt(), $group->changedAt());

        // Re-save it, but do not change the title
        $group->setTitle('bouh');
        $group->setIsGhost(true);
        $group->setAttribute('foo', ['a' => 'b']);
        $groupManager->save($group, ['is_ghost', 'is_meta', 'attributes']);

        // Load instance of group, should not be the same instance, and the
        // title has not changed, the rest has
        $otherGroup = $groupManager->findOne($group->getId());
        $this->assertNotSame($otherGroup, $group);
        $this->assertSame('foo_bar_bar', $otherGroup->getTitle());
        $this->assertSame(['foo' => ['a' => 'b']], $otherGroup->getAttributes());
        $this->assertFalse($otherGroup->isMeta());
        $this->assertTrue($otherGroup->isGhost());
    }
}
