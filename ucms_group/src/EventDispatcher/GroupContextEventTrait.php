<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;

trait GroupContextEventTrait
{
    protected $groupManager;
    protected $siteManager;
    protected $currentUser;

    /**
     * Default constructor
     *
     * @param GroupManager $groupManager
     * @param SiteManager $siteManager
     * @param AccountInterface $currentUser
     */
    public function __construct(GroupManager $groupManager, SiteManager $siteManager, AccountInterface $currentUser)
    {
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
        $this->currentUser = $currentUser;
    }

    /**
     * Find most relevant group in context
     *
     * @return int
     *   May be null if nothing found
     */
    private function findMostRelevantGroupId()
    {
        if ($this->siteManager->hasDependentContext('group')) {

            /** @var \MakinaCorpus\Ucms\Group\Group $group */
            $group = $this->siteManager->getDependentContext('group');

            if ($group) {
                return (int)$group->getId();
            }
        }

        // There is no context, this means we need to check with user current
        // groups instead; and set the first one we find onto the node
        $accessList = $this->groupManager->getAccess()->getUserGroups($this->currentUser);
        if ($accessList) {
            return (int)reset($accessList)->getGroupId();
        }
    }

    /**
     * Same as findMostRelevantGroupId() but returning the group object
     *
     * @return null|\MakinaCorpus\Ucms\Group\Group
     */
    private function findMostRelevantGroup()
    {
        if ($id = $this->findMostRelevantGroupId()) {
            return $this->groupManager->getStorage()->findOne($id);
        }
    }

    /**
     * Find most relevant ghost value for node
     *
     * @return int
     */
    private function findMostRelevantGhostValue(NodeInterface $node)
    {
        if (!empty($node->group_id)) {
            return (int)$this->groupManager->getStorage()->findOne($node->group_id)->isGhost();
        }

        return 1;
    }
}
