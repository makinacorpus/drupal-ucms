<?php

namespace MakinaCorpus\Ucms\Group\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Site\Tests\SiteTestTrait;
use MakinaCorpus\Ucms\Group\Error\GroupMoveDisallowedException;

class GroupAccessTest extends AbstractDrupalTest
{
    use GroupTestTrait;
    use SiteTestTrait;

    protected function setUp()
    {
        parent::setUp();

        if (!$this->moduleExists('ucms_group')) {
            $this->markTestSkipped("You must enable the ucms_group module to run this test");
            return;
        }
    }

    protected function tearDown()
    {
        $this->eraseGroupData();
        $this->eraseAllData();

        parent::tearDown();
    }

    public function testMemberList()
    {
        $storage  = $this->getGroupManager()->getStorage();
        $access   = $this->getGroupManager()->getAccess();

        $group1 = new Group();
        $group1->setTitle('foo');
        $storage->save($group1);

        $group2 = new Group();
        $group2->setTitle('bar');
        $storage->save($group2);

        $user1 = $this->createDrupalUser();
        $user2 = $this->createDrupalUser();
        $user3 = $this->createDrupalUser();

        $this->assertUserNotInGroup($group1->getId(), $user1->id());
        $this->assertUserNotInGroup($group1->getId(), $user2->id());
        $this->assertUserNotInGroup($group1->getId(), $user3->id());
        $this->assertUserNotInGroup($group2->getId(), $user1->id());
        $this->assertUserNotInGroup($group2->getId(), $user2->id());
        $this->assertUserNotInGroup($group2->getId(), $user3->id());

        // Add some members
        $this->assertTrue($access->addMember($group1->getId(), $user1->id()));
        // Adding twice the same member should return false
        $this->assertFalse($access->addMember($group1->getId(), $user1->id()));
        $this->assertTrue($access->addMember($group1->getId(), $user3->id()));
        $this->assertTrue($access->addMember($group2->getId(), $user2->id()));

        $this->assertUserInGroup($group1->getId(), $user1->id());
        $this->assertUserNotInGroup($group1->getId(), $user2->id());
        $this->assertUserInGroup($group1->getId(), $user3->id());
        $this->assertUserNotInGroup($group2->getId(), $user1->id());
        $this->assertUserInGroup($group2->getId(), $user2->id());
        $this->assertUserNotInGroup($group2->getId(), $user3->id());

        // Delete some members, first one is not in group
        $access->removeMember($group2->getId(), $user1->id());
        // Second one is a real group member
        $access->removeMember($group1->getId(), $user3->id());
        // Add another back so we have at least 2 groups for him
        $access->addMember($group1->getId(), $user2->id());

        $this->assertUserInGroup($group1->getId(), $user1->id());
        $this->assertUserInGroup($group1->getId(), $user2->id());
        $this->assertUserNotInGroup($group1->getId(), $user3->id());
        $this->assertUserNotInGroup($group2->getId(), $user1->id());
        $this->assertUserInGroup($group2->getId(), $user2->id());
        $this->assertUserNotInGroup($group2->getId(), $user3->id());
    }

    public function testSiteList()
    {
        $storage  = $this->getGroupManager()->getStorage();
        $access   = $this->getGroupManager()->getAccess();

        $group1 = new Group();
        $group1->setTitle('foo');
        $storage->save($group1);

        $group2 = new Group();
        $group2->setTitle('bar');
        $storage->save($group2);

        $site1 = $this->createDrupalSite();
        $site2 = $this->createDrupalSite();
        $site3 = $this->createDrupalSite();

        $this->assertSiteNotInGroup($group1->getId(), $site1->getId());
        $this->assertSiteNotInGroup($group1->getId(), $site2->getId());
        $this->assertSiteNotInGroup($group1->getId(), $site3->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site1->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site2->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site3->getId());

        // Add some sites
        $this->assertTrue($access->addSite($group1->getId(), $site1->getId()));
        // Adding twice the same site should return false
        $this->assertFalse($access->addSite($group1->getId(), $site1->getId()));
        $this->assertTrue($access->addSite($group1->getId(), $site3->getId()));
        $this->assertTrue($access->addSite($group2->getId(), $site2->getId()));

        $this->assertSiteInGroup($group1->getId(), $site1->getId());
        $this->assertSiteNotInGroup($group1->getId(), $site2->getId());
        $this->assertSiteInGroup($group1->getId(), $site3->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site1->getId());
        $this->assertSiteInGroup($group2->getId(), $site2->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site3->getId());

        // Attempt to move a site
        try {
            $access->addSite($group1->getId(), $site2->getId());
            $this->fail();
        } catch (GroupMoveDisallowedException $e) {}

        // Ensure nothing has changed
        $this->assertSiteInGroup($group2->getId(), $site2->getId());
        $this->assertSiteNotInGroup($group1->getId(), $site2->getId());

        // Attempt to move a site
        $access->addSite($group1->getId(), $site2->getId(), true);

        // Ensure everything has changed
        $this->assertSiteNotInGroup($group2->getId(), $site2->getId());
        $this->assertSiteInGroup($group1->getId(), $site2->getId());
    }
}
