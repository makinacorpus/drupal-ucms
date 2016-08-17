<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupMember;

class GroupMemberDeleteProcessor extends AbstractActionProcessor
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

        parent::__construct($this->t("Delete"), 'trash', 500, false, true, true, 'edit');
    }

    public function getId()
    {
        return 'group_member_delete';
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

        /** @var \MakinaCorpus\Ucms\Group\GroupMember $item */
        $group = $this->groupManager->getStorage()->findOne($item->getGroupId());

        return $this->groupManager->getAccess()->userCanManageMembers($this->currentUser, $group);
    }

    public function processAll($items)
    {
        /** @var \MakinaCorpus\Ucms\Group\GroupMember $item */
        foreach ($items as $item) {
            $this->groupManager->getAccess()->removeMember($item->getGroupId(), $item->getUserId());
        }

        return $this->formatPlural(
            count($item),
            "Group member has been removed",
            "@count group members have been removed"
        );
    }

    public function getItemId($item)
    {
        /** @var \MakinaCorpus\Ucms\Group\GroupMember $item */
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
