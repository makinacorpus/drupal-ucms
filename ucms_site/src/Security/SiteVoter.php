<?php

namespace MakinaCorpus\Ucms\Site\Security;

use MakinaCorpus\Drupal\Sf\Security\DrupalUser;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteAccessService;
use MakinaCorpus\Ucms\Site\SiteState;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * @todo would it be a good idea to directly implement it on the site access manager?
 */
final class SiteVoter implements VoterInterface
{
    private $siteAccess;

    public function __construct(SiteAccessService $siteAccess)
    {
        $this->siteAccess = $siteAccess;
    }

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (!$subject instanceof Site) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();
        if (!$user instanceof DrupalUser) {
            return self::ACCESS_ABSTAIN;
        }

        $account = $user->getDrupalAccount();

        // This is a shortcut, no matter the attribute, there is a god permission.
        if ($account->hasPermission(Access::PERM_SITE_GOD)) {
            return self::ACCESS_GRANTED;
        }

        $isSiteManager = $account->hasPermission(Access::PERM_SITE_MANAGE_ALL);
        $isSiteTech = $account->hasPermission(Access::PERM_SITE_IS_TECHNICIAN);
        $state = $subject->getState();

        foreach ($attributes as $attribute) {

            if (!\is_string($attribute)) {
                continue;
            }

            // This is authoritative, and it allows other modules to prevent
            // something to be done.
            if ($this->siteAccess->accessIsDenied($account, $subject, $attribute)) {
                continue;
            }

            switch ($attribute) {

                case Access::OP_VIEW: // Browse to site.

                    // Public sites are public.
                    if (SiteState::ON === $state || $isSiteManager || $isSiteTech) {
                        return self::ACCESS_GRANTED;
                    }

                    // @todo this should be based upon a matrix, or ACL's.
                    switch ($state) {

                        case SiteState::INIT:
                        case SiteState::ARCHIVE:
                            if (
                                $account->hasPermission(Access::PERM_SITE_VIEW_ALL) ||
                                $this->siteAccess->userIsWebmaster($account, $subject)
                            ) {
                                return self::ACCESS_GRANTED;
                            }
                            break;

                        case SiteState::OFF:
                            if (
                                $account->hasPermission(Access::PERM_SITE_VIEW_ALL) ||
                                $this->userIsWebmaster($account, $subject) ||
                                $this->userIsContributor($account, $subject)
                            ) {
                                return self::ACCESS_GRANTED;
                            }
                            break;
                    }
                    break;

                case Access::OP_SITE_VIEW_IN_ADMIN:

                    if ($isSiteManager || $isSiteTech) {
                        return self::ACCESS_GRANTED;
                    }

                    switch ($state) {

                        case SiteState::INIT:
                        case SiteState::OFF:
                        case SiteState::ON:
                            // @todo replace this with "user is member"
                            if (
                                $this->siteAccess->userIsContributor($account, $subject) ||
                                $this->siteAccess->userIsWebmaster($account, $subject)
                            ) {
                                return self::ACCESS_GRANTED;
                            }
                            break;

                        default:
                            if ($this->siteAccess->userIsWebmaster($account, $subject)) {
                                return self::ACCESS_GRANTED;
                            }
                            break;
                    }
                    break;

                case Access::OP_SITE_MANAGE_MENUS:

                    if ($isSiteManager || $isSiteTech) {
                        return self::ACCESS_GRANTED;
                    }

                    // @todo this should be based upon a matrix, or ACL's.
                    switch ($state) {

                        case SiteState::INIT:
                        case SiteState::ARCHIVE:
                        case SiteState::OFF:
                            if ($this->siteAccess->userIsWebmaster($account, $subject)) {
                                return self::ACCESS_GRANTED;
                            }
                            break;
                    }
                    break;

                case Access:OP_SITE_CHANGE_HOSTNAME:
                    if ($isSiteTech) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_UPDATE:
                    if ($isSiteManager || $isSiteTech) {
                        return true;
                    }

                    // @todo this should be based upon a matrix, or ACL's.
                    switch ($state) {

                        case SiteState::INIT:
                        case SiteState::OFF:
                        case SiteState::ON:
                            if ($this->siteAccess->userIsWebmaster($account, $subject)) {
                                return self::ACCESS_GRANTED;
                            }
                            break;
                    }
                    break;

                case Access::OP_SITE_MANAGE_WEBMASTERS:
                    if ($isSiteManager || $isSiteTech || $this->siteAccess->userIsWebmaster($account, $subject)) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_DELETE:
                    if (SiteState::ARCHIVE === $state && ($isSiteManager || $isSiteTech)) {
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
