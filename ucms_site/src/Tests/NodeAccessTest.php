<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Node;
use Drupal\node\NodeInterface;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\NodeAccessService;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use function WebDriver\accept_alert;

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

        $this->getNodeHelper()->resetCache();

        return $account;
    }

    protected function createDrupalNode($status = 0, $site = null, $otherSites = [], $isGlobal = false, $isGroup = false, $isClonable = false)
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
        $this->getNodeHelper()->resetCache();

        return $this;
    }

    protected function whenIAmAnonymous()
    {
        $this->contextualAccount = $this->getAnonymousUser();
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
                    ->userCanAccess(
                        $this->contextualAccount,
                        $this->getNode($label),
                        Access::OP_VIEW
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
                    ->userCanAccess(
                        $this->contextualAccount,
                        $this->getNode($label),
                        Access::OP_VIEW
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
                    ->userCanAccess(
                        $this->contextualAccount,
                        $this->getNode($label),
                        Access::OP_UPDATE
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
                    ->userCanAccess(
                        $this->contextualAccount,
                        $this->getNode($label),
                        Access::OP_UPDATE
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

    protected function canCreate($label)
    {
        $site = $this->getSiteManager()->getContext();

        $this
            ->assertSame(
                NODE_ACCESS_ALLOW,
                $this
                    ->getNodeHelper()
                    ->userCanAccess(
                        $this->contextualAccount,
                        $label,
                        Access::OP_CREATE
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
                NODE_ACCESS_DENY,
                $this
                    ->getNodeHelper()
                    ->userCanAccess(
                        $this->contextualAccount,
                        $label,
                        Access::OP_CREATE
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
        $this->nodes['global_locked_published']         = $this->createDrupalNode(1, null, [], true, false, false);
        $this->nodes['global_locked_unpublished']       = $this->createDrupalNode(0, null, [], true, false, false);
        $this->nodes['global_published']                = $this->createDrupalNode(1, null, [], true, false, true);
        $this->nodes['global_unpublished']              = $this->createDrupalNode(0, null, [], true, false, true);

        $this->nodes['group_locked_published']          = $this->createDrupalNode(1, null, [], true, true, false);
        $this->nodes['group_locked_unpublished']        = $this->createDrupalNode(0, null, [], true, true, false);
        $this->nodes['group_published']                 = $this->createDrupalNode(1, null, [], true, true, true);
        $this->nodes['group_unpublished']               = $this->createDrupalNode(0, null, [], true, true, true);

        $this->nodes['in_on_global_locked_published']   = $this->createDrupalNode(1, null, ['on'], true, false, false);
        $this->nodes['in_on_global_locked_unpublished'] = $this->createDrupalNode(0, null, ['on'], true, false, false);
        $this->nodes['in_on_global_published']          = $this->createDrupalNode(1, null, ['on'], true, false, true);
        $this->nodes['in_on_global_unpublished']        = $this->createDrupalNode(0, null, ['on'], true, false, true);

        $this->nodes['in_on_group_locked_published']    = $this->createDrupalNode(1, null, ['on'], true, true, false);
        $this->nodes['in_on_group_locked_unpublished']  = $this->createDrupalNode(0, null, ['on'], true, true, false);
        $this->nodes['in_on_group_published']           = $this->createDrupalNode(1, null, ['on'], true, true, true);
        $this->nodes['in_on_group_unpublished']         = $this->createDrupalNode(0, null, ['on'], true, true, true);

        $this->nodes['site_on_locked_published']        = $this->createDrupalNode(1, 'on', [], false, false, false);
        $this->nodes['site_on_locked_unpublished']      = $this->createDrupalNode(0, 'on', [], false, false, false);
        $this->nodes['site_on_published']               = $this->createDrupalNode(1, 'on', [], false, false, true);
        $this->nodes['site_on_unpublished']             = $this->createDrupalNode(0, 'on', [], false, false, true);
        $this->nodes['site_off_published']              = $this->createDrupalNode(1, 'off', [], false, false, true);
        $this->nodes['site_off_unpublished']            = $this->createDrupalNode(0, 'off', [], false, false, true);
        $this->nodes['site_init_published']             = $this->createDrupalNode(1, 'init', [], false, false, true);
        $this->nodes['site_init_unpublished']           = $this->createDrupalNode(0, 'init', [], false, false, true);
        $this->nodes['site_archive_published']          = $this->createDrupalNode(1, 'archive', [], false, false, true);
        $this->nodes['site_archive_unpublished']        = $this->createDrupalNode(0, 'archive', [], false, false, true);
        $this->nodes['site_pending_published']          = $this->createDrupalNode(1, 'pending', [], false, false, true);
        $this->nodes['site_pending_unpublished']        = $this->createDrupalNode(0, 'pending', [], false, false, true);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
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

                ->canSeeAll()
                ->canEditNone()
                // Please note, and this is IMPORTANT, that the canDo*
                // methods are not affected by the site context, because
                // the NodeAccessService won't use the context to check
                // those, either you can do stuff with that node, either
                // you cannot.
                // THIS IS TRUE FOR ALL OTHER TEST CASES. NO NEED TO REPEAT
                // THOSE TESTS IN EACH SITE CONTEXT, IT WONT CHANGE A THING!
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')

            ->whenIAm([Access::PERM_CONTENT_MANAGE_GLOBAL])

                ->canSeeOnly([
                    'global_locked_published',
                    'global_locked_unpublished',
                    'global_published',
                    'global_unpublished',
                    'in_on_global_locked_published',
                    'in_on_global_locked_unpublished',
                    'in_on_global_published',
                    'in_on_global_unpublished',
                ])
                ->canEditOnly([
                    'global_locked_published',
                    'global_locked_unpublished',
                    'global_published',
                    'global_unpublished',
                    'in_on_global_locked_published',
                    'in_on_global_locked_unpublished',
                    'in_on_global_published',
                    'in_on_global_unpublished',
                ])
                ->canCreateOnly($this->getTypeHandler()->getEditorialTypes())
                ->canDoNone('clone')
                ->canDoOnly('lock', [
                    'global_locked_published',
                    'global_locked_unpublished',
                    'global_published',
                    'global_unpublished',
                    'in_on_global_locked_published',
                    'in_on_global_locked_unpublished',
                    'in_on_global_published',
                    'in_on_global_unpublished',
                ])
                ->canDoNone('promote')
                //->canDoNone('reference')

           ->whenIAm([Access::PERM_CONTENT_MANAGE_GROUP])

                ->canSeeOnly([
                    'group_locked_published',
                    'group_locked_unpublished',
                    'group_published',
                    'group_unpublished',
                    'in_on_group_locked_published',
                    'in_on_group_locked_unpublished',
                    'in_on_group_published',
                    'in_on_group_unpublished',
                ])
                ->canEditOnly([
                    'group_locked_published',
                    'group_locked_unpublished',
                    'group_published',
                    'group_unpublished',
                    'in_on_group_locked_published',
                    'in_on_group_locked_unpublished',
                    'in_on_group_published',
                    'in_on_group_unpublished',
                ])
                ->canCreateOnly($this->getTypeHandler()->getEditorialTypes())
                ->canDoNone('clone')
                ->canDoOnly('lock', [
                    'group_locked_published',
                    'group_locked_unpublished',
                    'group_published',
                    'group_unpublished',
                    'in_on_group_locked_published',
                    'in_on_group_locked_unpublished',
                    'in_on_group_published',
                    'in_on_group_unpublished',
                ])
                ->canDoOnly('promote', [
                    'global_locked_published',
                    'global_locked_unpublished',
                    'global_published',
                    'global_unpublished',
                    'in_on_global_locked_published',
                    'in_on_global_locked_unpublished',
                    'in_on_global_published',
                    'in_on_global_unpublished',
                    'group_locked_published',
                    'group_locked_unpublished',
                    'group_published',
                    'group_unpublished',
                    'in_on_group_locked_published',
                    'in_on_group_locked_unpublished',
                    'in_on_group_published',
                    'in_on_group_unpublished',
                ])
                //->canDoNone('reference')

            ->whenIAm([Access::PERM_CONTENT_VIEW_GLOBAL])

                ->canSeeOnly([
                    'global_locked_published',
                    'global_published',
                    'in_on_global_locked_published',
                    'in_on_global_published',
                ])
                ->canEditNone()
                ->canCreateNone()
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')

            ->whenIAm([Access::PERM_CONTENT_VIEW_GROUP])

                ->canSeeOnly([
                    'group_locked_published',
                    'group_published',
                    'in_on_group_locked_published',
                    'in_on_group_published',
                ])
                ->canEditNone()
                ->canCreateNone()
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;
    }

    public function testWebmasterRights()
    {
        $this
            ->whenIAm([], ['on' => Access::ROLE_WEBMASTER])
                ->canSeeOnly([
                    'site_on_published',
                    'site_on_unpublished',
                    'site_on_locked_published',
                    'site_on_locked_unpublished',
                    'in_on_global_locked_published',
                    'in_on_global_published',
                    'in_on_group_locked_published',
                    'in_on_group_published',
                ])
                ->canEditOnly([
                    'site_on_published',
                    'site_on_unpublished',
                    'site_on_locked_published',
                    'site_on_locked_unpublished',
                ])
                ->canCreateNone()
                // FIXME: I need some referenced nodes
                // ->canDoOnly('clone')
                ->canDoOnly('lock', [
                    'site_on_published',
                    'site_on_unpublished',
                    'site_on_locked_published',
                    'site_on_locked_unpublished',
                ])
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;

        // Another site's webmaster may only see his content
        $this
            ->whenIAm([], ['off' => Access::ROLE_WEBMASTER])

                ->canSeeOnly([
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canEditOnly([
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canCreateNone()
                // FIXME: I need some referenced nodes
                // ->canDoOnly('clone')
                ->canDoOnly('lock', [
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;

        // But the same with the permission of seeing global content might see
        // other's sites global content, as readonly, and also see other sites
        // content
        $this
            ->whenIAm([
                Access::PERM_CONTENT_VIEW_GLOBAL,
                Access::PERM_CONTENT_VIEW_GROUP,
                Access::PERM_CONTENT_VIEW_OTHER
            ], ['off' => Access::ROLE_WEBMASTER])
                ->canSeeOnly([
                    'site_on_published',
                    'site_on_locked_published',
                    'global_locked_published',
                    'global_published',
                    'group_locked_published',
                    'group_published',
                    'in_on_global_locked_published',
                    'in_on_global_published',
                    'in_on_group_locked_published',
                    'in_on_group_published',
                    'site_off_published',
                    'site_off_unpublished',
                    // As a side effect of the 'view other' permission, the user
                    // may see content from sites which are archived or in other
                    // states, there is no easy way to fix this. Please refer to
                    // the NodeAccessService::getNodeGrants() inline code
                    // documentation for details.
                    'site_init_published',
                    'site_archive_published',
                    'site_pending_published',
                ])
                ->canEditOnly([
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canCreateNone()
                // FIXME: I need some referenced nodes
                // ->canDoOnly('clone')
                ->canDoOnly('lock', [
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;

        $this
            ->whenIAm([], ['archive' => Access::ROLE_WEBMASTER])

                ->canSeeOnly([
                    'site_archive_published',
                    'site_archive_unpublished',
                ])
                ->canEditNone()
                ->canCreateNone()
                // FIXME: I need some referenced nodes
                // ->canDoOnly('clone')
                ->canDoOnly('lock', [
                    'site_archive_published',
                    'site_archive_unpublished',
                ])
                ->canDoNone('promote')
                //->canDoNone('reference')

            ->whenIAm([], ['pending' => Access::ROLE_WEBMASTER])

                ->canSeeNone()
                ->canEditNone()
                ->canCreateNone()
                // FIXME: I need some referenced nodes
                // ->canDoOnly('clone')
                // FIXME: Node site target should be checked for
                // [init, off, on] states upon those methods
                // ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;
    }

    public function testContributorRights()
    {
        $this
            ->whenIAm([], ['on' => Access::ROLE_CONTRIB])

                ->canSeeOnly([
                    'site_on_published',
                    'site_on_unpublished',
                    'site_on_locked_published',
                    'site_on_locked_unpublished',
                    'in_on_global_locked_published',
                    'in_on_global_published',
                    'in_on_group_locked_published',
                    'in_on_group_published',
                ])
                ->canEditNone()
                ->canCreateNone()
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')

            ->whenIAm([], ['off' => Access::ROLE_CONTRIB])

                ->canSeeOnly([
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canEditNone()

            ->whenIAm([], ['archive' => Access::ROLE_CONTRIB])

                ->canSeeNone()
                ->canEditNone()
                ->canCreateNone()

            ->whenIAm([], ['pending' => Access::ROLE_CONTRIB])

                ->canSeeNone()
                ->canEditNone()
                ->canCreateNone()
        ;
    }

    public function testContributorCanEditHisOwnContent()
    {
        $this->whenIAm([], ['off' => Access::ROLE_CONTRIB]);
        $contibutor = $this->contextualAccount;

        // Site the user is into with content belonging to him
        $this->getNode('site_off_unpublished')->setOwnerId($contibutor->id());
        // Site the user is into, but belonging to another user
        $this->getNode('site_off_published')->setOwnerId(1);
        // Another site the user is not into, should not be able to edit
        $this->getNode('site_on_published')->setOwnerId($contibutor->id());

        $this->canEdit('site_off_unpublished');
        $this->canNotEdit('site_off_published');
        $this->canNotEdit('site_on_published');
    }

    public function testAnonymousRights()
    {
        $this->getSiteManager()->setContext($this->getSite('on'));

        $this
            ->whenIAmAnonymous()
                ->canSeeOnly([
                    'site_on_published',
                    'site_on_locked_published',
                    'in_on_global_locked_published',
                    'in_on_global_published',
                    'in_on_group_locked_published',
                    'in_on_group_published',
                ])
                ->canEditNone()
                ->canCreateNone()
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;

        $this->getSiteManager()->setContext($this->getSite('off'));

        $this
            ->whenIAmAnonymous()
            ->canSeeNone()
            ->canEditNone()
            ->canCreateNone()
            ->canDoNone('clone')
            ->canDoNone('lock')
            ->canDoNone('promote')
            //->canDoNone('reference')
        ;

        $this->getSiteManager()->dropContext();
    }

    public function testNoRoleAuthRights()
    {
        $this->getSiteManager()->setContext($this->getSite('on'));

        $this
            ->whenIAm([])
                ->canSeeOnly([
                    'site_on_published',
                    'site_on_locked_published',
                    'in_on_global_locked_published',
                    'in_on_global_published',
                    'in_on_group_locked_published',
                    'in_on_group_published',
                ])
                ->canEditNone()
                ->canCreateNone()
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;

        $this->getSiteManager()->setContext($this->getSite('off'));

        $this
            ->whenIAm([])
                ->canSeeNone()
                ->canEditNone()
                ->canCreateNone()
                ->canDoNone('clone')
                ->canDoNone('lock')
                ->canDoNone('promote')
                //->canDoNone('reference')
        ;

        $this->getSiteManager()->dropContext();
    }
}
