<?php

namespace MakinaCorpus\Ucms\Site\Security;

use MakinaCorpus\Drupal\Sf\Security\DrupalUser;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @todo would it be a good idea to directly implement it on the group manager?
 */
final class GroupVoter implements VoterInterface
{
    private $groupManager;

    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (!$subject instanceof Group) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();
        if (!$user instanceof DrupalUser) {
            return self::ACCESS_ABSTAIN;
        }

        $account = $user->getDrupalAccount();
        $isGroupSuperAdmin = $account->hasPermission(Access::PERM_GROUP_MANAGE_ALL);
        $isGroupMeta = $subject->isMeta();

        // This is a shortcut, no matter the attribute, there is a god permission.
        if ($isGroupSuperAdmin && !$isGroupMeta) {
            return self::ACCESS_GRANTED;
        }

        foreach ($attributes as $attribute) {

            if (!\is_string($attribute)) {
                continue;
            }

            switch ($attribute) {

                case Access::OP_VIEW:
                    if ($isGroupSuperAdmin || $this->groupManager->userIsMember($account, $subject)) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_UPDATE:
                    if ($isGroupSuperAdmin) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_DELETE:
                    if ($isGroupSuperAdmin && !$isGroupMeta) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_GROUP_MANAGE_SITES:
                    // @todo should it be allowed for group admins?
                    if ($isGroupSuperAdmin) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_GROUP_MANAGE_MEMBERS:
                    // @todo should it be allowed for group admins?
                    if ($isGroupSuperAdmin) {
                        return self::ACCESS_GRANTED;
                    }
                    break;
            }
        }

        // This is a bit restrive, in theory, if no attributes are supported
        // the result should be abstain instead. But hey, don't hack my module.
        return self::ACCESS_DENIED;
    }
}
