<?php

namespace MakinaCorpus\Ucms\Contrib\NodeAccess;

use MakinaCorpus\Ucms\Contrib\TypeHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\EventDispatcher\NodeAccessEvent;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Uses the abstraction provided by the sf_dic module to collect node access
 * grants and user grants, so benefit from the generic method it provides
 * to intersect those at runtime.
 */
final class NodeAccessEventSubscriber implements EventSubscriberInterface
{
    private $siteManager;
    private $typeHandler;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param TypeHandler $typeHandler
     */
    public function __construct(SiteManager $siteManager, TypeHandler $typeHandler)
    {
        $this->siteManager = $siteManager;
        $this->typeHandler = $typeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            NodeAccessEvent::EVENT_NODE_ACCESS => [
                ['onNodeAccess', 48],
            ],
        ];
    }

    /**
     * Checks node access for content creation
     */
    public function onNodeAccess(NodeAccessEvent $event)
    {
        $node     = $event->getNode();
        $account  = $event->getAccount();
        $op       = $event->getOperation();
        $access   = $this->siteManager->getAccess();

        if (Access::OP_CREATE === $op) {

            // Drupal gave a wrong input, this may happen
            if (!is_string($node) && !$node instanceof NodeInterface) {
                return $this->deny();
            }
            $type = is_string($node) ? $node : $node->bundle();

            // Locked types
            if (in_array($type, $this->typeHandler->getLockedTypes()) && !$account->hasPermission(Access::PERM_CONTENT_GOD)) {
                return $event->deny();
            }

            if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();

                // Contributor can only create editorial content
                if ($access->userIsContributor($account, $site) && in_array($type, $this->typeHandler->getEditorialTypes())) {
                    return $event->allow();
                }

                // Webmasters can create anything
                if ($access->userIsWebmaster($account, $site) && in_array($type, $this->typeHandler->getAllTypes())) {
                    return $event->allow();
                }
            } else {

                // All user that may manage global content or manage group
                // content may create content outside of a site context, as
                // long as content is editorial (text and media)
                $canManage =
                    $account->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL) ||
                    $account->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)
                ;

                if ($canManage && in_array($type, $this->typeHandler->getEditorialTypes())) {
                    return $event->allow();
                }
            }
        } else if (Access::OP_DELETE === $op) {
            // Locked types
            if (in_array($node->bundle(), $this->typeHandler->getLockedTypes()) && !$account->hasPermission(Access::PERM_CONTENT_GOD)) {
                return $event->deny();
            }
        }

        return $event->ignore();
    }
}
