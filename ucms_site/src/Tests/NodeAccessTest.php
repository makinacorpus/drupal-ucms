<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Container\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\NodeAccessHelper;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

/**
 * @todo
 *   Fix hardcoded roles identifiers
 */
class NodeAccessTest extends AbstractDrupalTest
{
    /**
     * @var int
     */
    protected $nidSeq = 1;

    /**
     * @var \stdClass[]
     */
    protected $nodes = [];

    /**
     * @var \stdClass[]
     */
    protected $accounts = [];

    /**
     * @var Site
     */
    protected $sites = [];

    /**
     * @var \stdClass
     */
    protected $contextualAccount;

    /**
     * Get Drupal anonymous user
     *
     * @return \stdClass
     */
    protected function getAnonymousUser()
    {
        return drupal_anonymous_user();
    }

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
     * @return NodeAccessHelper
     */
    protected function getNodeHelper()
    {
        return $this->getDrupalContainer()->get('ucms_site.node_access_helper');
    }

    /**
     * Create a Drupal user
     *
     * @param string[] $permissionList
     *   Permission string list
     * @param string[] $siteMap
     *   Keys are site labels, values are Access::ROLE_* constants
     *
     * @return \stdClass
     */
    protected function createDrupalUser($permissionList = [], $siteMap = [])
    {
        $account = new \stdClass();
        $this->accounts[] = $account;
        $stupidHash = uniqid() . mt_rand();
        $account->name = $stupidHash;
        $account->mail = $stupidHash . '@example.com';
        $account->roles = [];
        user_save($account);

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

        // Fake user access cache for testing
        $data = &drupal_static('user_access');
        $data[$account->uid] = array_combine($permissionList, $permissionList);

        return $account;
    }

    protected function createDrupalNode($status = 0, $site = null, $otherSites = [], $isGlobal = false, $isClonable = false)
    {
        $node = new \stdClass();
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
        $node->is_clonable = (int)(bool)$isClonable;
        foreach ($otherSites as $label) {
            $node->ucms_sites[] = $this->getSite($label)->id;
        }
        $node->ucms_sites = array_unique($node->ucms_sites);
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

        return $this;
    }

    protected function whenIAmAnonymous()
    {
        $this->contextualAccount = $this->getAnonymousUser();

        return $this;
    }

    protected function inSite($label)
    {
        $this->getSiteManager()->setContext($this->getSite($label));
        $this->getNodeHelper()->resetCache();

        return $this;
    }

    protected function getOutSite()
    {
        $this->getSiteManager()->dropContext();
        $this->getNodeHelper()->resetCache();

        return $this;
    }

    protected function canSee($label)
    {
        $this
            ->assertSame(
                NODE_ACCESS_ALLOW,
                $this
                    ->getNodeHelper()
                    ->canUserAccess(
                        $this->getNode($label),
                        Access::OP_VIEW,
                        $this->contextualAccount
                    ),
                sprintf("Can see %s", $label)
            )
        ;

        return $this;
    }

    protected function canNotSee($label)
    {
        $this
            ->assertSame(
                NODE_ACCESS_DENY,
                $this
                    ->getNodeHelper()
                    ->canUserAccess(
                        $this->getNode($label),
                        Access::OP_VIEW,
                        $this->contextualAccount
                    ),
                sprintf("Can not see %s", $label)
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
        $this
            ->assertSame(
                NODE_ACCESS_ALLOW,
                $this
                    ->getNodeHelper()
                    ->canUserAccess(
                        $this->getNode($label),
                        Access::OP_UPDATE,
                        $this->contextualAccount
                    ),
                sprintf("Can edit %s", $label)
            )
        ;

        return $this;
    }

    protected function canNotEdit($label)
    {
        $this
            ->assertSame(
                NODE_ACCESS_DENY,
                $this
                    ->getNodeHelper()
                    ->canUserAccess(
                        $this->getNode($label),
                        Access::OP_UPDATE,
                        $this->contextualAccount
                    ),
                sprintf("Can not edit %s", $label)
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

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sites['on']        = $this->createDrupalSite(SiteState::ON);
        $this->sites['off']       = $this->createDrupalSite(SiteState::OFF);
        $this->sites['init']      = $this->createDrupalSite(SiteState::INIT);
        $this->sites['archive']   = $this->createDrupalSite(SiteState::ARCHIVE);
        $this->sites['pending']   = $this->createDrupalSite(SiteState::PENDING);

        // Create false set of nodes, a lot of them.
        $this->nodes['global_locked_published']         = $this->createDrupalNode(1, null, [], true, false);
        $this->nodes['global_locked_invisible']         = $this->createDrupalNode(0, null, [], true, false);
        $this->nodes['global_published']                = $this->createDrupalNode(1, null, [], true, true);
        $this->nodes['global_invisible']                = $this->createDrupalNode(0, null, [], true, true);
        $this->nodes['site_on_published']               = $this->createDrupalNode(1, 'on', [], false, true);
        $this->nodes['site_on_unpublished']             = $this->createDrupalNode(0, 'on', [], false, true);
        $this->nodes['site_on_locked_published']        = $this->createDrupalNode(1, 'on', [], false, false);
        $this->nodes['site_on_locked_unpublished']      = $this->createDrupalNode(0, 'on', [], false, false);
        $this->nodes['in_on_global_locked_published']   = $this->createDrupalNode(1, null, ['on'], true, false);
        $this->nodes['in_on_global_locked_unpublished'] = $this->createDrupalNode(0, null, ['on'], true, false);
        $this->nodes['in_on_global_published']          = $this->createDrupalNode(1, null, ['on'], true, true);
        $this->nodes['in_on_global_unpublished']        = $this->createDrupalNode(0, null, ['on'], true, true);
        $this->nodes['site_off_published']              = $this->createDrupalNode(1, 'off', [], false, true);
        $this->nodes['site_off_unpublished']            = $this->createDrupalNode(0, 'off', [], false, true);
        $this->nodes['site_init_published']             = $this->createDrupalNode(1, 'init', [], false, true);
        $this->nodes['site_init_unpublished']           = $this->createDrupalNode(0, 'init', [], false, true);
        $this->nodes['site_archive_published']          = $this->createDrupalNode(1, 'archive', [], false, true);
        $this->nodes['site_archive_unpublished']        = $this->createDrupalNode(0, 'archive', [], false, true);
        $this->nodes['site_pending_published']          = $this->createDrupalNode(1, 'pending', [], false, true);
        $this->nodes['site_pending_unpublished']        = $this->createDrupalNode(0, 'pending', [], false, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        foreach ($this->accounts as $account) {
            user_delete($account->uid);
        }
        foreach ($this->sites as $site) {
            $this->getSiteManager()->getStorage()->delete($site);
        }

        $this->getSiteManager()->dropContext();
        $this->contextualAccount = null;

        parent::tearDown();
    }

    public function testGlobalAdminRights()
    {
        $this
            ->whenIAm([Access::PERM_CONTENT_VIEW_ALL])

                ->getOutSite()
                    ->canSeeAll()
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeOnly([
                        'site_off_published',
                        'site_off_unpublished',
                    ])
                    ->canEditNone()

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_unpublished',
                        'site_on_locked_published',
                        'site_on_locked_unpublished',
                        'in_on_global_locked_published',
                        'in_on_global_locked_unpublished',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])
                    ->canEditNone()

            ->whenIAm([Access::PERM_CONTENT_MANAGE_GLOBAL])

                ->getOutSite()
                    ->canSeeOnly([
                        'global_locked_published',
                        'global_locked_invisible',
                        'global_published',
                        'global_invisible',
                        'in_on_global_locked_published',
                        'in_on_global_locked_unpublished',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])
                    ->canEditOnly([
                        'global_published',
                        'global_invisible',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                    ])
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeNone()
                    ->canEditNone()

           ->whenIAm([Access::PERM_CONTENT_MANAGE_GLOBAL_LOCKED])

                ->getOutSite()
                    ->canSeeOnly([
                        'global_locked_published',
                        'global_locked_invisible',
                        'global_published',
                        'global_invisible',
                        'in_on_global_locked_published',
                        'in_on_global_locked_unpublished',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])
                    ->canEditOnly([
                        'global_locked_published',
                        'global_locked_invisible',
                        'global_published',
                        'global_invisible',
                        'in_on_global_locked_published',
                        'in_on_global_locked_unpublished',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                    ])
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeNone()
                    ->canEditNone()

            ->whenIAm([Access::PERM_CONTENT_VIEW_GLOBAL])

                ->getOutSite()
                    ->canSeeOnly([
                        'global_locked_published',
                        'global_published',
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                        // @todo This should not be true:
                        'site_off_published',
                        'site_init_published',
                        'site_archive_published',
                        'site_pending_published',
                    ])
                    ->canEditNone()

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                    ])
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeNone()
                    ->canEditNone()
        ;
    }

    public function testWebmasterRights()
    {
        $this
            ->whenIAm([], ['on' => Access::ROLE_WEBMASTER])

                ->getOutSite()
                    /*
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_unpublished',
                        'site_on_locked_published',
                        'site_on_locked_unpublished',
                        'in_on_global_locked_published',
                        'in_on_global_locked_unpublished',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])
                    ->canEditOnly([
                        'site_on_published',
                        'site_on_unpublished',
                        'site_on_locked_published',
                        'site_on_locked_unpublished',
                    ])
                      */

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_unpublished',
                        'site_on_locked_published',
                        'site_on_locked_unpublished',
                        'in_on_global_locked_published',
                        'in_on_global_locked_unpublished',
                        'in_on_global_published',
                        'in_on_global_unpublished',
                    ])
                    ->canEditOnly([
                        'site_on_published',
                        'site_on_unpublished',
                        'site_on_locked_published',
                        'site_on_locked_unpublished',
                    ])

                ->inSite('off')
                    ->canSeeNone()
                    ->canEditNone()

            ->whenIAm([], ['off' => Access::ROLE_WEBMASTER])

                ->getOutSite()
                    /*
                    ->canSeeOnly([
                        'site_off_published',
                        'site_off_unpublished',
                    ])
                    ->canEditOnly([
                        'site_off_published',
                        'site_off_unpublished',
                    ])
                      */

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                    ])
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeOnly([
                        'site_off_published',
                        'site_off_unpublished',
                    ])
                    ->canEditOnly([
                        'site_off_published',
                        'site_off_unpublished',
                    ])

            ->whenIAm([], ['archive' => Access::ROLE_WEBMASTER])

                ->getOutSite()
                    /*
                    ->canSeeOnly([
                        'site_archive_published',
                        'site_archive_unpublished',
                    ])
                    ->canEditNone()
                      */

                ->inSite('archive')
                    ->canSeeOnly([
                        'site_archive_published',
                        'site_archive_unpublished',
                    ])
                    ->canEditNone()

            ->whenIAm([], ['pending' => Access::ROLE_WEBMASTER])

                ->getOutSite()
                    ->canSeeNone()
                    ->canEditNone()

                ->inSite('pending')
                    ->canSeeNone()
                    ->canEditNone()
        ;
    }

    public function testContributorRights()
    {
        // @todo
    }

    public function testAnonymousRights()
    {
        $this
            ->whenIAmAnonymous()

                ->getOutSite()
                    ->canSeeNone()
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeNone()
                    ->canEditNone()

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                    ])
                    ->canEditNone()
        ;
    }

    public function testNoRoleAuthRights()
    {
        $this
            ->whenIAm([])

                ->getOutSite()
                    ->canSeeNone()
                    ->canEditNone()

                ->inSite('off')
                    ->canSeeNone()
                    ->canEditNone()

                ->inSite('on')
                    ->canSeeOnly([
                        'site_on_published',
                        'site_on_locked_published',
                        'in_on_global_locked_published',
                        'in_on_global_published',
                    ])
                    ->canEditNone()
        ;
    }
}
