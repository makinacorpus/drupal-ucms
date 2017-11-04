<?php

namespace MakinaCorpus\Ucms\Group\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Drupal\Calista\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;

class GroupDeleteProcessor extends AbstractActionProcessor
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
        return 'group_delete';
    }

    public function getQuestion($items, $totalCount)
    {
        return $this->formatPlural(
            $totalCount,
            "Delete this group?",
            "Delete the selected @count groups?"
        );
    }

    public function appliesTo($item)
    {
        return $item instanceof Group && $this->isGranted(Permission::DELETE, $item);
    }

    public function processAll($items)
    {
        /** @var \MakinaCorpus\Ucms\Group\Group $item */
        foreach ($items as $item) {
            $this->groupManager->getStorage()->delete($item, $this->currentUser->id());
        }

        return $this->formatPlural(
            count($item),
            "Group has been deleted",
            "@count groups have been deleted"
        );
    }

    public function getItemId($item)
    {
        /** @var \MakinaCorpus\Ucms\Group\Group $item */
        return $item->getId();
    }

    public function loadItem($id)
    {
        return $this->groupManager->getStorage()->findOne($id);
    }
}
