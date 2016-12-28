<?php

namespace MakinaCorpus\Ucms\Contrib\NodeAccess;

use MakinaCorpus\Ucms\Contrib\ContentTypeManager;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Permission;
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
    private $contentTypeManager;

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param ContentTypeManager $contentTypeManager
     */
    public function __construct(SiteManager $siteManager, ContentTypeManager $contentTypeManager)
    {
        $this->siteManager = $siteManager;
        $this->contentTypeManager = $contentTypeManager;
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
     *
     * @param AccountInterface $account
     * @param NodeInterface|string $node
     * @param string $op
     * @param Site $site
     * @return string
     */
    public function onNodeAccess(NodeAccessEvent $event)
    {
        $node     = $event->getNode();
        $account  = $event->getAccount();
        $op       = $event->getOperation();
        $access   = $this->siteManager->getAccess();

        if ('create' === $op) {
            // Drupal gave a wrong input, this may happen
            if (!is_string($node) && !$node instanceof NodeInterface) {
                return $this->deny();
            }

            $type = is_string($node) ? $node : $node->bundle();

            // Locked types
            if (
                in_array($type, $this->contentTypeManager->getLockedTypes()) &&
                !$account->hasPermission(Access::PERM_CONTENT_GOD)
            ) {
                return $event->deny();
            }

            if ($this->siteManager->hasContext()) {
                $site = $this->siteManager->getContext();

                // Contributor can only create editorial content
                if (
                    $access->userIsContributor($account, $site) &&
                    in_array($type, $this->contentTypeManager->getNonComponentTypes())
                ) {
                    return $event->allow();
                }

                // Webmasters can create anything
                if (
                    $access->userIsWebmaster($account, $site) &&
                    in_array($type, $this->contentTypeManager->getAllTypes())
                ) {
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

                if ($canManage && in_array($type, $this->contentTypeManager->getNonComponentTypes())) {
                    return $event->allow();
                }
            }
        } else if (Permission::DELETE === $op) {
            // Locked types
            if (
                in_array($node->bundle(), $this->contentTypeManager->getLockedTypes()) &&
                !$account->hasPermission(Access::PERM_CONTENT_GOD)
            ) {
                return $event->deny();
            }
        }

        return $event->ignore();
    }
}
