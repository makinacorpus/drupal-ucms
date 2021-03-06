<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\Error\GroupMoveDisallowedException;

class GroupAccessTest extends AbstractDrupalTest
{
    use SiteTestTrait;

    protected function setUp()
    {
        if (!$this->moduleExists('ucms_group')) {
            $this->markTestSkipped("You must enable the ucms_group module to run this test");
        }

        parent::setUp();
    }

    protected function tearDown()
    {
        $this->eraseAllData();

        parent::tearDown();
    }

    public function testMemberList()
    {
        $manager = $this->getGroupManager();

        $group1 = new Group();
        $group1->setTitle('foo');
        $manager->save($group1);

        $group2 = new Group();
        $group2->setTitle('bar');
        $manager->save($group2);

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
        $this->assertTrue($manager->addMember($group1->getId(), $user1->id()));
        // Adding twice the same member should return false
        $this->assertFalse($manager->addMember($group1->getId(), $user1->id()));
        $this->assertTrue($manager->addMember($group1->getId(), $user3->id()));
        $this->assertTrue($manager->addMember($group2->getId(), $user2->id()));

        $this->assertUserInGroup($group1->getId(), $user1->id());
        $this->assertUserNotInGroup($group1->getId(), $user2->id());
        $this->assertUserInGroup($group1->getId(), $user3->id());
        $this->assertUserNotInGroup($group2->getId(), $user1->id());
        $this->assertUserInGroup($group2->getId(), $user2->id());
        $this->assertUserNotInGroup($group2->getId(), $user3->id());

        // Delete some members, first one is not in group
        $manager->removeMember($group2->getId(), $user1->id());
        // Second one is a real group member
        $manager->removeMember($group1->getId(), $user3->id());
        // Add another back so we have at least 2 groups for him
        $manager->addMember($group1->getId(), $user2->id());

        $this->assertUserInGroup($group1->getId(), $user1->id());
        $this->assertUserInGroup($group1->getId(), $user2->id());
        $this->assertUserNotInGroup($group1->getId(), $user3->id());
        $this->assertUserNotInGroup($group2->getId(), $user1->id());
        $this->assertUserInGroup($group2->getId(), $user2->id());
        $this->assertUserNotInGroup($group2->getId(), $user3->id());
    }

    public function testSiteList()
    {
        $manager = $this->getGroupManager();

        $group1 = new Group();
        $group1->setTitle('foo');
        $manager->save($group1);

        $group2 = new Group();
        $group2->setTitle('bar');
        $manager->save($group2);

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
        $this->assertTrue($manager->addSite($group1->getId(), $site1->getId(), true));
        // Adding twice the same site should return false
        $this->assertFalse($manager->addSite($group1->getId(), $site1->getId(), true));
        $this->assertTrue($manager->addSite($group1->getId(), $site3->getId(), true));
        $this->assertTrue($manager->addSite($group2->getId(), $site2->getId(), true));

        $this->assertSiteInGroup($group1->getId(), $site1->getId());
        $this->assertSiteNotInGroup($group1->getId(), $site2->getId());
        $this->assertSiteInGroup($group1->getId(), $site3->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site1->getId());
        $this->assertSiteInGroup($group2->getId(), $site2->getId());
        $this->assertSiteNotInGroup($group2->getId(), $site3->getId());

        // Attempt to move a site
        try {
            $manager->addSite($group1->getId(), $site2->getId());
            $this->fail();
        } catch (GroupMoveDisallowedException $e) {}

        // Ensure nothing has changed
        $this->assertSiteInGroup($group2->getId(), $site2->getId());
        $this->assertSiteNotInGroup($group1->getId(), $site2->getId());

        // Attempt to move a site
        $manager->addSite($group1->getId(), $site2->getId(), true);

        // Ensure everything has changed
        $this->assertSiteNotInGroup($group2->getId(), $site2->getId());
        $this->assertSiteInGroup($group1->getId(), $site2->getId());
    }
}
