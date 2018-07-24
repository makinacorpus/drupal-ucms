<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\GroupEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Drupal\Core\Database\Connection;

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

        $listener = function (GroupEvent $event) {
            $this->groups[] = $event->getGroup();
        };
        \Closure::bind($listener, $this);
        $dispatcher->addListener(GroupEvent::EVENT_CREATE, $listener);

        return new GroupManager($database, $dispatcher);
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
            $groupManager = $this->getGroupManager();

            foreach ($this->groups as $group) {
                $groupManager->delete($group);
            }
        }
    }

    /**
     * Get database connection
     */
    abstract protected function getDatabaseConnection(): Connection;

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
