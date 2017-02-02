<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupSite;

class GroupSiteRemoveProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    protected $groupManager;
    protected $currentUser;

    /**
     * Default constructor
     *
     * @param GroupManager $groupManager
     * @param AccountInterface $currentUser
     */
    public function __construct(GroupManager $groupManager, AccountInterface $currentUser)
    {
        $this->groupManager = $groupManager;
        $this->currentUser = $currentUser;

        parent::__construct($this->t("Remove"), 'remove', 500, true, true, true, 'edit');
    }

    public function getId()
    {
        return 'group_site_remove';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Remove this site from this group?",
            "Remove the selected @count sites from this group?"
        );
    }

    public function appliesTo($item)
    {
        if (!$item instanceof GroupSite) {
            return false;
        }

        /** @var \MakinaCorpus\Ucms\Group\GroupSite $item */
        $group = $this->groupManager->getStorage()->findOne($item->getGroupId());

        return $this->groupManager->getAccess()->userCanManageSites($this->currentUser, $group);
    }

    public function processAll($items)
    {
        /** @var \MakinaCorpus\Ucms\Group\GroupSite $item */
        foreach ($items as $item) {
            $this->groupManager->getAccess()->removeSite($item->getGroupId(), $item->getSiteId());
        }

        return $this->formatPlural(
            count($item),
            "Site has been removed from this group",
            "@count sites have been removed from this group"
        );
    }

    public function getItemId($item)
    {
        /** @var \MakinaCorpus\Ucms\Group\GroupSite $item */
        return $item->getGroupId() . ':' . $item->getSiteId();
    }

    public function loadItem($id)
    {
        list ($groupId, $siteId) = explode(':', $id);

        // This is somehow bad, because we are creating a partial partial user
        // implementation, with name, email and status missing, but it's only
        // to pass throught requests and form state, and will not happen to
        // be displayed in any template, so get over it!
        return GroupSite::create($groupId, $siteId);
    }
}
