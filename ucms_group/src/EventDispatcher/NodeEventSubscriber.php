<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeEvent;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupSite;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add group functionnality on nodes
 */
class NodeEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

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

    /**
     * @var GroupManager
     */
    private $groupManager;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * Default constructor
     *
     * @param GroupManager $groupManager
     * @param SiteManager $siteManager
     */
    public function __construct(GroupManager $groupManager, SiteManager $siteManager)
    {
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
    }

    /**
     * Find most relevant group in context
     *
     * @return GroupSite
     *   May be null if nothing found
     */
    private function findMostRelevantGroup()
    {
        if ($this->siteManager->hasDependentContext('group')) {

            /** @var \MakinaCorpus\Ucms\Group\GroupSite $accessList */
            $accessList = $this->siteManager->getDependentContext('group');

            // @todo Should we filter using the current user groups?
            if ($accessList) {
                return reset($accessList);
            }
        }
    }

    /**
     * Find most relevant ghost value for node
     *
     * @return bool
     */
    private function findMostRelevantGhostValue(NodeInterface $node)
    {
        if ($node->group_id) {
            return $this->groupManager->getStorage()->findOne($node->group_id)->isGhost();
        }

        return true;
    }

    /**
     * Sets the most relevant 'group_id' and 'is_ghost' property values
     */
    public function onNodePrepare(NodeEvent $event)
    {
        $node = $event->getNode();

        if ($node->group_id) {
            // Someone took care of this for us
            return;
        }

        $access = $this->findMostRelevantGroup();
        if (!$access) {
            return;
        }

        $node->group_id = $access->getGroupId();
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
        if (!$node->group_id) {
            $access = $this->findMostRelevantGroup();

            if ($access) {
                $node->is_ghost = (int)$this->findMostRelevantGhostValue($node);
            } else {
                // Sorry, but I do must reset that for data consistency
                $node->is_ghost = 0;
            }
        }
    }
}
