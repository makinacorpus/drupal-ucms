<?php

namespace MakinaCorpus\Ucms\Group\EventDispatcher;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\AllowListEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteAccessEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add group functionnality on sites
 */
class SiteEventSubscriber implements EventSubscriberInterface
{
    use GroupContextEventTrait;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            SiteEvents::EVENT_ACCESS => [
                ['onSiteAccess', 0]
            ],
            SiteEvents::EVENT_PRECREATE => [
                ['onSitePreCreate', 0]
            ],
            AllowListEvent::EVENT_THEMES => [
                ['onAllowedThemeList', 0]
            ],
        ];
    }

    /**
     * Forbid site access whenever user is not in the same group.
     *
     * Its alter-ego in SQL queries is implemented using hook_query_TAG_alter()
     * where tag is 'site_access'.
     */
    public function onSiteAccess(SiteAccessEvent $event)
    {
        $site = $event->getSite();
        $account = $event->getUserAccount();

        if ($account->hasPermission(Access::PERM_SITE_GOD)) {
            return $event->allow();
        }

        if (!empty($site->group_id)) {
            foreach ($this->groupManager->getAccess()->getUserGroups($account) as $group) {
                if ($group->getGroupId() == $site->group_id) {
                    return $event->allow();
                }
            }
        }

        return $event->deny();
    }

    /**
     * Sets the most relevant 'group_id' property values
     */
    public function onSitePreCreate(SiteEvent $event)
    {
        $site = $event->getSite();

        if (!empty($site->group_id)) {
            return; // Someone took care of this for us
        }

        $site->group_id = $this->findMostRelevantGroupId();
    }

    /**
     * Restrict theme list to what group supports
     */
    public function onAllowedThemeList(AllowListEvent $event)
    {
        if ($group = $this->findMostRelevantGroup()) {
            if ($themes = $group->getAttribute('allowed_themes')) {
                $event->removeNotIn($themes);
            }
        }
    }
}
