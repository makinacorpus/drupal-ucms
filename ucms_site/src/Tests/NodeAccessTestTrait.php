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
     *
     * @return AccountInterface
     */
    protected function createDrupalUser($permissionList = [], $siteMap = [])
    {
        // Ahah, 2 hours debugging. No matter how hard you attempt to implement
        // node_access API, if the user has no 'access content' permission, bye
        // bye custom implementations and hooks!
        $permissionList[] = 'access content';

        $account = parent::createDrupalUser($permissionList);

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

        return $account;
    }

    protected function createDrupalNode($status = 0, $site = null, $otherSites = [], $isGlobal = false, $isGroup = false, $isClonable = false, $other = [])
    {
        $node = new Node();
        $node->nid = $this->nidSeq++;
        $node->status = (int)(bool)$status;
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
        return $node;
    }

    protected function createDrupalSite($state)
    {
        $site = new Site();
        $stupidHash = uniqid() . mt_rand();
        $site->state = (int)$state;
        $site->title = $stupidHash;
        $site->title_admin = $stupidHash;
        $site->http_host = $stupidHash . '.example.com';
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

    protected function whenIAm($permissionList = [], $siteMap = [])
    {
        $this->contextualAccount = $this->createDrupalUser($permissionList, $siteMap);

        $this->getSiteManager()->getAccess()->resetCache();
        //$this->get('sf_dic.node_access.subscriber')->resetCache();

        return $this;
    }

    protected function whenIAmAnonymous()
    {
        $this->contextualAccount = $this->getAnonymousUser();

        $this->getSiteManager()->getAccess()->resetCache();

        return $this;
    }

    protected function canSee($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                true,
                $this
                    ->getNodeHelper()
                    ->userCanView(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("Can see %s on site %s", $label, $site ? SiteState::getList()[$site->state] : '<None>')
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
                    ->getNodeHelper()
                    ->userCanView(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("Can not see %s on site %s", $label, $site ? SiteState::getList()[$site->state] : '<None>')
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
                    ->getNodeHelper()
                    ->userCanEdit(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("Can edit %s on site %s", $label, $site ? SiteState::getList()[$site->state] : '<None>')
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
                    ->getNodeHelper()
                    ->userCanEdit(
                        $this->contextualAccount,
                        $this->getNode($label)
                    ),
                sprintf("Can not edit %s on site %s", $label, $site ? SiteState::getList()[$site->state] : '<None>')
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
                sprintf("Cannot create %s on site %s", $label, $site ? SiteState::getList()[$site->state] : '<None>')
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
                sprintf("Cannot create %s on site %s", $label, $site ? SiteState::getList()[$site->state] : '<None>' )
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

    protected function canDoReally($op, $label)
    {
        $node = $this->getNode($label);
        $account = $this->contextualAccount;

        switch ($op) {

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
                throw new \InvalidArgumentException("\$op can be only one of 'lock', 'clone', 'promote', 'reference'");
        }

        return $success;
    }

    protected function canDo($op, $label)
    {
        $site = $this->getSiteManager()->getContext();

        $this->assertTrue($this->canDoReally($op, $label), sprintf("Can %s %s on site %s", $op, $label, $site ? SiteState::getList()[$site->state] : '<None>'));

        return $this;
    }

    protected function canNotDo($op, $label)
    {
        $site = $this->getSiteManager()->getContext();

        $this->assertFalse($this->canDoReally($op, $label), sprintf("Cannot %s %s on site %s", $op, $label, $site ? SiteState::getList()[$site->state] : '<None>'));

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
