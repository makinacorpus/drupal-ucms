<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\GroupMember;

class GroupMemberRemoveProcessor extends AbstractActionProcessor
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
        return 'group_member_remove';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Remove this member from this group?",
            "Remove the selected @count members for this group?"
        );
    }

    public function appliesTo($item)
    {
        if (!$item instanceof GroupMember) {
            return false;
        }

        /** @var \MakinaCorpus\Ucms\Site\GroupMember $item */
        $group = $this->groupManager->findOne($item->getGroupId());

        return $this->groupManager->userCanManageMembers($this->currentUser, $group);
    }

    public function processAll($items)
    {
        /** @var \MakinaCorpus\Ucms\Site\GroupMember $item */
        foreach ($items as $item) {
            $this->groupManager->removeMember($item->getGroupId(), $item->getUserId());
        }

        return $this->formatPlural(
            count($item),
            "Group member has been removed",
            "@count group members have been removed"
        );
    }

    public function getItemId($item)
    {
        /** @var \MakinaCorpus\Ucms\Site\GroupMember $item */
        return $item->getGroupId() . ':' . $item->getUserId();
    }

    public function loadItem($id)
    {
        list ($groupId, $userId) = explode(':', $id);

        // This is somehow bad, because we are creating a partial partial user
        // implementation, with name, email and status missing, but it's only
        // to pass throught requests and form state, and will not happen to
        // be displayed in any template, so get over it!
        return GroupMember::create($groupId, $userId);
    }
}
