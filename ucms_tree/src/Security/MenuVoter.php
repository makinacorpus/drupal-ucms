<?php

namespace MakinaCorpus\Ucms\Tree\Security;

use MakinaCorpus\Drupal\Sf\Security\DrupalUser;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

final class MenuVoter implements VoterInterface
{
    private $siteManager;

    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
    }

    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        if (!$subject instanceof Menu) {
            return self::ACCESS_ABSTAIN;
        }

        $user = $token->getUser();
        if (!$user instanceof DrupalUser) {
            return self::ACCESS_ABSTAIN;
        }
        $account = $user->getDrupalAccount();

        if (!$siteId = $subject->getSiteId()) {
            return self::ACCESS_ABSTAIN;
        }
        $site = $this->siteManager->getStorage()->findOne($siteId);

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute)) {
                continue;
            }

            switch ($attribute) {

                case Access::OP_VIEW:
                case Access::OP_UPDATE:
                    if ($this->siteManager->getAccess()->userCanEditTree($account, $site)) {
                        return self::ACCESS_GRANTED;
                    }
                    break;

                case Access::OP_DELETE:
                    return self::ACCESS_DENIED; // @todo fixme
                    break;
            }
        }

        // This is a bit restrive, in theory, if no attributes are supported
        // the result should be abstain instead. But hey, don't hack my module.
        return self::ACCESS_DENIED;
    }
}
