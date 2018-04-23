<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Node;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

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
     * Get node access helper
     *
     * @return NodeAccessService
     */
    protected function getNodeAccessSubscriber()
    {
        return $this->getDrupalContainer()->get('ucms_site.node_access.subscriber');
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
            /** @var \MakinaCorpus\Ucms\Site\GroupManager $groupManager */
            $groupManager = $this->getDrupalContainer()->get('ucms_group.manager');
            $groupManager->addMember($groupId, $account->id());
        }

        return $account;
    }

    protected function createDrupalNode($status = 0, $site = null, $otherSites = [], $isGlobal = false, $isCorporate = false, $isClonable = false, $other = [])
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
        if ($isCorporate) {
            $title[] = 'corporate';
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
        $node->is_group = (int)(bool)$isCorporate;
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

        // property_exists() cannot be replaced with isset() or empty() because
        // callers might explicitely set it to 0 or null; since we don't call
        // node_object_preprare() the property will no be set.
        if (!property_exists($node, 'group_id')) {
            $node->group_id = $this->getDefaultGroupId();
        }

        return $node;
    }

    private function getDefaultGroupId()
    {
        if (!$this->defaultGroupId) {
            $this->defaultGroupId = $this->getDrupalContainer()->get('database')->query("SELECT id FROM {ucms_group} WHERE is_meta = 1 ORDER BY id ASC")->fetchField();
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
            return $this->contextualAccount->getAccountName() . ' (' . $this->contextualAccount->uid . ')';
        }
    }

    protected function canSee($label)
    {
        $site = $this->getSiteManager()->getContext();
        $node = $this->getNode($label);

        $this
            ->assertSame(
                true,
                $this
                    ->getNodeHelper()
                    ->userCanView(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("%s can see %s (%s) on site %s", $this->whoIAm(), $label, $node->nid, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canNotSee($label)
    {
        $site = $this->getSiteManager()->getContext();
        $node = $this->getNode($label);

        $this
            ->assertSame(
                false,
                $this
                    ->getNodeHelper()
                    ->userCanView(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("%s can not see %s (%s) on site %s", $this->whoIAm(), $label, $node->nid, $site ? SiteState::getList()[$site->state] : '<None>')
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
        $node = $this->getNode($label);

        $this
            ->assertSame(
                true,
                $this
                    ->getNodeHelper()
                    ->userCanEdit(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("%s can edit %s (%s) on site %s", $this->whoIAm(), $label, $node->nid, $site ? SiteState::getList()[$site->state] : '<None>')
            )
        ;

        return $this;
    }

    protected function canNotEdit($label)
    {
        $site = $this->getSiteManager()->getContext();
        $node = $this->getNode($label);

        $this
            ->assertSame(
                false,
                $this
                    ->getNodeHelper()
                    ->userCanEdit(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("%s can not edit %s (%s) on site %s", $this->whoIAm(), $label, $node->nid, $site ? SiteState::getList()[$site->state] : '<None>')
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

        switch ($permission) {

            case 'clone':
                $success = $this->getNodeHelper()->userCanDuplicate($account, $node);
                break;

            case 'lock':
                $success = $this->getNodeHelper()->userCanLock($account, $node);
                break;

            case 'promote':
                $success = $this->getNodeHelper()->userCanPromoteToGroup($account, $node);
                break;

            case 'reference':
                $success = $this->getNodeHelper()->userCanReference($account, $node);
                break;

            case 'dereference':
                $success = $this->getNodeHelper()->userCanDereference($account, $node);
                break;

            default:
                throw new \InvalidArgumentException("\$permission can be only one of 'lock', 'clone', 'promote', 'reference': '%s' given", $permission);
        }

        return $success;
    }

    protected function canDo($op, $label)
    {
        $site = $this->getSiteManager()->getContext();
        $node = $this->getNode($label);

        $this->assertTrue($this->canDoReally($op, $label), sprintf("%s can %s %s (%s) on site %s", $this->whoIAm(), $op, $label, $node->uid, $site ? SiteState::getList()[$site->state] : '<None>'));

        return $this;
    }

    protected function canNotDo($op, $label)
    {
        $site = $this->getSiteManager()->getContext();
        $node = $this->getNode($label);

        $this->assertFalse($this->canDoReally($op, $label), sprintf("%s can NOT %s %s (%s) on site %s", $this->whoIAm(), $op, $label, $node->nid, $site ? SiteState::getList()[$site->state] : '<None>'));

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
