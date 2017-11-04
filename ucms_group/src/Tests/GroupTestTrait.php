<?php

namespace MakinaCorpus\Ucms\Group\Tests;

use MakinaCorpus\Ucms\Group\EventDispatcher\GroupEvent;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupAccessService;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupStorage;

use Symfony\Component\EventDispatcher\EventDispatcher;

trait GroupTestTrait
{
    private $manager;
    private $groups = [];

    /**
     * Build testing group manager instance
     *
     * @return GroupManager
     */
    private function createManager()
    {
        $database   = $this->getDatabaseConnection();
        $dispatcher = new EventDispatcher();
        $storage    = new GroupStorage($database, $dispatcher);
        $access     = new GroupAccessService($database, $storage);

        $listener = function (GroupEvent $event) {
            $this->groups[] = $event->getGroup();
        };
        \Closure::bind($listener, $this);
        $dispatcher->addListener(GroupEvent::EVENT_CREATE, $listener);

        return new GroupManager($database, $storage, $access);
    }

    /**
     * Get testing group manager
     *
     * @return GroupManager
     */
    final protected function getGroupManager()
    {
        if (!$this->manager) {
            $this->manager = $this->createManager();
        }

        return $this->manager;
    }

    /**
     * Erase all data for tear down
     */
    final protected function eraseGroupData()
    {
        if ($this->groups) {
            $storage = $this->getGroupManager()->getStorage();

            foreach ($this->groups as $group) {
                $storage->delete($group);
            }
        }
    }

    /**
     * @return \DatabaseConnection
     */
    abstract protected function getDatabaseConnection();

    /**
     * If you don't implement tearDown() this'll do it for you, if you do
     * implement tearDown() please manually call eraseGroupData() there
     */
    protected function tearDown()
    {
        $this->eraseGroupData();

        parent::tearDown();
    }

    /**
     * Asserts that user is in group
     */
    final protected function assertUserInGroup($groupId, $userId)
    {
        $exists = (bool)$this->getDatabaseConnection()->query("SELECT 1 FROM {ucms_group_access} WHERE user_id = :u AND group_id = :g", [':u' => $userId, ':g' => $groupId])->fetchField();

        $this->assertTrue($exists);
    }

    /**
     * Asserts that user is not in group
     */
    final protected function assertUserNotInGroup($groupId, $userId)
    {
        $exists = (bool)$this->getDatabaseConnection()->query("SELECT 1 FROM {ucms_group_access} WHERE user_id = :u AND group_id = :g", [':u' => $userId, ':g' => $groupId])->fetchField();

        $this->assertFalse($exists);
    }

    /**
     * Asserts that site is in group
     */
    final protected function assertSiteInGroup($groupId, $siteId)
    {
        $exists = (bool)$this->getDatabaseConnection()->query("SELECT 1 FROM {ucms_site} WHERE id = :s AND group_id = :g", [':s' => $siteId, ':g' => $groupId])->fetchField();

        $this->assertTrue($exists);
    }

    /**
     * Asserts that site is not in group
     */
    final protected function assertSiteNotInGroup($groupId, $siteId)
    {
        $exists = (bool)$this->getDatabaseConnection()->query("SELECT 1 FROM {ucms_site} WHERE id = :s AND group_id = :g", [':s' => $siteId, ':g' => $groupId])->fetchField();

        $this->assertFalse($exists);
    }
}
