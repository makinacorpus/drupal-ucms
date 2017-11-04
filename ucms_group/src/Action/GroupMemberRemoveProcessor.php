<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Drupal\Calista\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\GroupMember;
use MakinaCorpus\Ucms\Site\Access;

class GroupMemberRemoveProcessor extends AbstractActionProcessor
{
    use StringTranslationTrait;

    protected $groupManager;

    /**
     * Default constructor
     */
    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;

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

        /** @var \MakinaCorpus\Ucms\Group\GroupMember $item */
        $group = $this->groupManager->getStorage()->findOne($item->getGroupId());

        return $this->isGranted(Access::ACL_PERM_MANAGE_USERS, $group);
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
        return $item->getGroupId() . ':' . $item->getUserId() . ':' . $item->getRoleMask();
    }

    public function loadItem($id)
    {
        list ($groupId, $userId, $role) = explode(':', $id);

        // This is somehow bad, because we are creating a partial partial user
        // implementation, with name, email and status missing, but it's only
        // to pass throught requests and form state, and will not happen to
        // be displayed in any template, so get over it!
        return GroupMember::create($groupId, $userId, $role);
    }
}
