<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\GroupSite;

class GroupSiteRemoveProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    protected $groupManager;
    protected $currentUser;

    /**
     * Default constructor
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

        /** @var \MakinaCorpus\Ucms\Site\GroupSite $item */
        $group = $this->groupManager->findOne($item->getGroupId());

        return $this->groupManager->userCanManageSites($this->currentUser, $group);
    }

    public function processAll($items)
    {
        /** @var \MakinaCorpus\Ucms\Site\GroupSite $item */
        foreach ($items as $item) {
            $this->groupManager->removeSite($item->getGroupId(), $item->getSiteId());
        }

        return $this->formatPlural(
            count($item),
            "Site has been removed from this group",
            "@count sites have been removed from this group"
        );
    }

    public function getItemId($item)
    {
        /** @var \MakinaCorpus\Ucms\Site\GroupSite $item */
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
