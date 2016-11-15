<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Node;
use Drupal\node\NodeInterface;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

trait NodeAccessTestTrait
{
    /**
     * @var int
     */
    protected $nidSeq = 1;

    /**
     * @var NodeInterface[]
     */
    protected $nodes = [];

    /**
     * @var Site
     */
    protected $sites = [];

    /**
     * @var AccountInterface
     */
    protected $contextualAccount;

    /**
     * @var int
     */
    private $defaultGroupId;

    /**
     * Get site manager
     *
     * @return SiteManager
     */
    protected function getSiteManager()
    {
        return $this->getDrupalContainer()->get('ucms_site.manager');
    }

    /**
     * Get node access helper
     *
     * @return NodeAccessService
     */
    protected function getNodeHelper()
    {
        return $this->getDrupalContainer()->get('ucms_site.node_access_helper');
    }

    /**
     * Get authorization checker
     *
     * @return AuthorizationCheckerInterface
     */
    protected function getAuthorizationChecker()
    {
        return \Drupal::service('security.authorization_checker');
    }

    /**
     * Get type handler
     *
     * @return TypeHandler
     */
    protected function getTypeHandler()
    {
        return $this->getDrupalContainer()->get('ucms_contrib.type_handler');
    }

    /**
     * Create a Drupal user
     *
     * @param string[] $permissionList
     *   Permission string list
     * @param string[] $siteMap
     *   Keys are site labels, values are Access::ROLE_* constants
     * @param string $name
     *   Account name for debugging purpose
     *
     * @return AccountInterface
     */
    protected function createDrupalUser($permissionList = [], $siteMap = [], $name = null)
    {
        // Ahah, 2 hours debugging. No matter how hard you attempt to implement
        // node_access API, if the user has no 'access content' permission, bye
        // bye custom implementations and hooks!
        $permissionList[] = 'access content';

        $account = parent::createDrupalUser($permissionList, $name);

        if ($siteMap) {
            foreach ($siteMap as $label => $role) {
                switch ($role) {
                    case Access::ROLE_WEBMASTER:
                        $this->getSiteManager()->getAccess()->addWebmasters($this->getSite($label), $account->uid);
                        break;
                    default:
                        $this->getSiteManager()->getAccess()->addContributors($this->getSite($label), $account->uid);
                        break;
                }
            }
        }

        // In case the 'ucms_group' module is enabled, tests will fail due
        // to restrictive group node access rights, add the user to a default
        // group
        $groupId = $this->getDefaultGroupId();
        if ($groupId) {
            /** @var \MakinaCorpus\Ucms\Group\GroupManager $groupManager */
            $groupManager = $this->getDrupalContainer()->get('ucms_group.manager');
            $groupManager->getAccess()->addMember($groupId, $account->id());
        }

        return $account;
    }

    protected function createDrupalNode($status = 0, $site = null, $otherSites = [], $isGlobal = false, $isGroup = false, $isClonable = false, $other = [])
    {
        $node = new Node();
        $node->nid = $this->nidSeq++;
        $node->status = (int)(bool)$status;

        // Create a sensible title on which you can break or watch using your
        // favorite debugger, go go XDebug!
        $title = [];
        if ($status) {
            $title[] = 'published';
        }
        if ($isGlobal) {
            $title[] = 'global';
        }
        if ($isGroup) {
            $title[] = 'group';
        }
        if (!$isClonable) {
            $title[] = 'locked';
        }
        if ($site) {
            $title[] = 'on ' . $site;
        }
        $node->title = implode(' ', $title);

        $node->ucms_sites = [];
        if ($site) {
            $site = $this->getSite($site);
            $node->site_id = $site->id;
            $node->ucms_sites = [$site->id];
        } else {
            $node->site_id = null;
            $node->ucms_sites = [];
        }

        $node->is_global = (int)(bool)$isGlobal;
        $node->is_group = (int)(bool)$isGroup;
        $node->is_clonable = (int)(bool)$isClonable;

        foreach ($otherSites as $label) {
            $node->ucms_sites[] = $this->getSite($label)->id;
        }
        $node->ucms_sites = array_unique($node->ucms_sites);

        if ($other) {
            foreach ($other as $key => $value) {
                $node->{$key} = $value;
            }
        }

        $node->group_id = $this->getDefaultGroupId();

        return $node;
    }

    private function getDefaultGroupId()
    {
        if (module_exists('ucms_group')) {
            // In case the 'ucms_group' module is enabled, tests will fail due
            // to restrictive group node access rights, set them right
            if (!$this->defaultGroupId) {
                $this->defaultGroupId = $this->getDrupalContainer()->get('database')->query("SELECT id FROM {ucms_group} WHERE is_meta = 1 ORDER BY id ASC")->fetchField();
            }
        }

        return $this->defaultGroupId;
    }

    protected function createDrupalSite($state)
    {
        $site = new Site();
        $stupidHash = uniqid() . mt_rand();
        $site->state = (int)$state;
        $site->title = $stupidHash;
        $site->title_admin = $stupidHash;
        $site->http_host = $stupidHash . '.example.com';
        $site->group_id = $this->getDefaultGroupId();

        $this->getSiteManager()->getStorage()->save($site);

        return $site;
    }

    protected function getSite($label)
    {
        if (isset($this->sites[$label])) {
            return $this->sites[$label];
        }

        throw new \InvalidArgumentException(sprintf("Please be serious while writing tests, %s is not a mocked site", $label));
    }

    protected function getNode($label)
    {
        if (isset($this->nodes[$label])) {
            return $this->nodes[$label];
        }

        throw new \InvalidArgumentException(sprintf("Please be serious while writing tests, %s is not a mocked node", $label));
    }

    protected function whenIAm($permissionList = [], $siteMap = [], $name = null)
    {
        $this->contextualAccount = $this->createDrupalUser($permissionList, $siteMap, $name);

        $this->getSiteManager()->getAccess()->resetCache();

        return $this;
    }

    protected function whenIAmAnonymous()
    {
        $this->contextualAccount = $this->getAnonymousUser();

        $this->getSiteManager()->getAccess()->resetCache();

        return $this;
    }

    protected function whoIAm()
    {
        if (!$this->contextualAccount || $this->contextualAccount->isAnonymous()) {
            return 'Anonymous';
        } else {
            return $this->contextualAccount->getAccountName();
        }
    }

    protected function canSee($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                true,
                $this
                    ->getAuthorizationChecker()
                    ->isGranted(
                        Permission::VIEW,
                        $this->getNode($label),
                        $this->contextualAccount
                    ),
                sprintf("%s can see %s on site %s", $this->whoIAm(), $label, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canNotSee($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                false,
                $this
                    ->getAuthorizationChecker()
                    ->isGranted(
                        Permission::VIEW,
                        $this->getNode($label),
                        $this->contextualAccount
                    ),
                sprintf("%s can not see %s on site %s", $this->whoIAm(), $label, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canSeeOnly($labelList)
    {
        if (!is_array($labelList)) {
            $labelList = [$labelList];
        }

        foreach (array_keys($this->nodes) as $id) {
            if (in_array($id, $labelList)) {
                $this->canSee($id);
            } else {
                $this->canNotSee($id);
            }
        }

        return $this;
    }

    protected function canSeeAll()
    {
        foreach (array_keys($this->nodes) as $id) {
            $this->canSee($id);
        }

        return $this;
    }

    protected function canSeeNone()
    {
        $this->canSeeOnly([]);

        return $this;
    }

    protected function canEdit($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                true,
                $this
                    ->getAuthorizationChecker()
                    ->isGranted(
                        Permission::UPDATE,
                        $this->getNode($label),
                        $this->contextualAccount
                    ),
                sprintf("%s can edit %s on site %s", $this->whoIAm(), $label, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canNotEdit($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                false,
                $this
                    ->getAuthorizationChecker()
                    ->isGranted(
                        Permission::UPDATE,
                        $this->getNode($label),
                        $this->contextualAccount
                    ),
                sprintf("%s can not edit %s on site %s", $this->whoIAm(), $label, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canEditOnly($labelList)
    {
        if (!is_array($labelList)) {
            $labelList = [$labelList];
        }

        foreach (array_keys($this->nodes) as $id) {
            if (in_array($id, $labelList)) {
                $this->canEdit($id);
            } else {
                $this->canNotEdit($id);
            }
        }

        return $this;
    }

    protected function canEditNone()
    {
        $this->canEditOnly([]);

        return $this;
    }

    protected function canCreate($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                true,
                $this
                    ->getNodeHelper()
                    ->userCanCreate(
                        $this->contextualAccount,
                        $label
                    ),
                sprintf("%s can NOT create %s on site %s", $this->whoIAm(), $label, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canNotCreate($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                false,
                $this
                    ->getNodeHelper()
                    ->userCanCreate(
                        $this->contextualAccount,
                        $label
                    ),
                sprintf("%s can NOT create %s on site %s", $this->whoIAm(), $label, $site ? SiteState::getList()[$site->state] : '<None>' )
            )
        ;

        return $this;
    }

    protected function canCreateOnly($labelList)
    {
        if (!is_array($labelList)) {
            $labelList = [$labelList];
        }

        foreach (array_keys(node_type_get_names()) as $id) {
            if (in_array($id, $labelList)) {
                $this->canCreate($id);
            } else {
                $this->canNotCreate($id);
            }
        }

        return $this;
    }

    protected function canCreateNone()
    {
        $this->canCreateOnly([]);

        return $this;
    }

    protected function canDoReally($permission, $label)
    {
        $node = $this->getNode($label);
        $account = $this->contextualAccount;

        return $this
            ->getAuthorizationChecker()
            ->isGranted(
                $permission,
                $node,
                $account
            )
        ;
    }

    protected function canDo($op, $label)
    {
        $site = $this->getSiteManager()->getContext();

        $this->assertTrue($this->canDoReally($op, $label), sprintf("%s can %s %s on site %s", $this->whoIAm(), $op, $label, $site ? SiteState::getList()[$site->state] : '<None>'));

        return $this;
    }

    protected function canNotDo($op, $label)
    {
        $site = $this->getSiteManager()->getContext();

        $this->assertFalse($this->canDoReally($op, $label), sprintf("%s can NOT %s %s on site %s", $this->whoIAm(), $op, $label, $site ? SiteState::getList()[$site->state] : '<None>'));

        return $this;
    }

    protected function canDoOnly($op, $labelList)
    {
        if (!is_array($labelList)) {
            $labelList = [$labelList];
        }

        foreach (array_keys($this->nodes) as $id) {
            if (in_array($id, $labelList)) {
                $this->canDo($op, $id);
            } else {
                $this->canNotDo($op, $id);
            }
        }

        return $this;
    }

    protected function canDoNone($op)
    {
        $this->canDoOnly($op, []);

        return $this;
    }

    protected function eraseAll()
    {
        foreach ($this->sites as $site) {
            $this->getSiteManager()->getStorage()->delete($site);
        }

        $this->getSiteManager()->dropContext();
        $this->contextualAccount = null;
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        $this->eraseAll();

        parent::tearDown();
    }
}
