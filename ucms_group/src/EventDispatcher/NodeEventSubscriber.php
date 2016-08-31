<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add group functionnality on nodes
 */
class NodeEventSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NodeEvent::EVENT_PREPARE => [
                ['onNodePrepare', 0]
            ],
            NodeEvent::EVENT_PRESAVE => [
                ['onNodePresave', 0]
            ],
        ];
    }

    private $groupManager;
    private $siteManager;
    private $currentUser;

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
     * Find most relevant ghost value for node
     *
     * @return int
     */
    private function findMostRelevantGhostValue(NodeInterface $node)
    {
        if ($node->group_id) {
            return (int)$this->groupManager->getStorage()->findOne($node->group_id)->isGhost();
        }

        return 1;
    }

    /**
     * Sets the most relevant 'group_id' and 'is_ghost' property values
     */
    public function onNodePrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($node->group_id) {
            return; // Someone took care of this for us
        }

        $node->group_id = $this->findMostRelevantGroupId();
        $node->is_ghost = (int)$this->findMostRelevantGhostValue($node);
    }

    /**
     * Prepare hook is no always called, this is why we do reproduce what does
     * happen during the prepare hook in the presave hook, if no value has
     * already been provided
     */
    public function onNodePresave(NodeEvent $event)
    {
        $node = $event->getNode();

        // When coming from the node form, node form has already been submitted
        // case in which, if relevant, a group identifier has already been set
        // and this code won't be execute. In the other hand, if the prepare
        // hook has not been invoked, this will run and set things right.
        // There is still a use case where the node comes from the node form but
        // there is no contextual group, case in which this code will wrongly
        // run, but hopefuly since it is just setting defaults, it won't change
        // the normal behavior.
        if (empty($node->group_id)) {
            $groupId = $this->findMostRelevantGroupId();

            if ($groupId) {
                $node->group_id = $groupId;
            }

            $node->is_ghost = $this->findMostRelevantGhostValue($node);
        }
    }
}
