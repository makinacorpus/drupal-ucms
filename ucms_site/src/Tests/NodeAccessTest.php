<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;

class NodeAccessTest extends AbstractDrupalTest
{
    use NodeAccessTestTrait;

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

        // This'll be useful for debug, no business meaning.
        foreach ($this->nodes as $index => $node) {
            $node->setTitle($index);
        }

        $this->getSiteManager()->dropContext();
    }

    public function testGlobalAdminRights()
    {
        $this
            ->whenIAm([Access::PERM_CONTENT_VIEW_ALL], [], 'user that can see all')

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

            ->whenIAm([Access::PERM_CONTENT_MANAGE_GLOBAL], [], 'global contributor')

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

           ->whenIAm([Access::PERM_CONTENT_MANAGE_GROUP], [], 'group administrator')

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

            ->whenIAm([Access::PERM_CONTENT_VIEW_GLOBAL], [], 'user that can see global content')

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

            ->whenIAm([Access::PERM_CONTENT_VIEW_GROUP], [], 'user that can see group content')

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

    public function testWebmasterCreateRights()
    {
        $this->getSiteManager()->setContext($this->getSite('init'));
        $this
            ->whenIAm([], ['init' => Access::ROLE_WEBMASTER], 'init webmaster')
                ->canCreateOnly($this->getTypeHandler()->getUnlockedTypes())
        ;

        $this->getSiteManager()->setContext($this->getSite('on'));
        $this
            ->whenIAm([], ['on' => Access::ROLE_WEBMASTER], 'on webmaster')
                ->canCreateOnly($this->getTypeHandler()->getUnlockedTypes())
        ;

        $this->getSiteManager()->setContext($this->getSite('off'));
        $this
            ->whenIAm([], ['off' => Access::ROLE_WEBMASTER], 'off webmaster')
                ->canCreateOnly($this->getTypeHandler()->getUnlockedTypes())
        ;

        $this->getSiteManager()->setContext($this->getSite('archive'));
        $this
            ->whenIAm([], ['archive' => Access::ROLE_WEBMASTER], 'archive webmaster')
                ->canCreateNone()
        ;

        $this->getSiteManager()->setContext($this->getSite('pending'));
        $this
            ->whenIAm([], ['pending' => Access::ROLE_WEBMASTER], 'pending webmaster')
                ->canCreateNone()
        ;
    }

    public function testWebmasterRights()
    {
        $this
            ->whenIAm([], ['on' => Access::ROLE_WEBMASTER], 'on webmaster')
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
            ->whenIAm([], ['off' => Access::ROLE_WEBMASTER], 'off webmaster')

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
            ], ['off' => Access::ROLE_WEBMASTER], 'off webmaster that can see global and group content')
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
            ->whenIAm([], ['archive' => Access::ROLE_WEBMASTER], 'archive webmaster')

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

            ->whenIAm([], ['pending' => Access::ROLE_WEBMASTER], 'pending webmaster')

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
            ->whenIAm([], ['on' => Access::ROLE_CONTRIB], 'on contributor')

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

            ->whenIAm([], ['off' => Access::ROLE_CONTRIB], 'off contributor')

                ->canSeeOnly([
                    'site_off_published',
                    'site_off_unpublished',
                ])
                ->canEditNone()

            ->whenIAm([], ['archive' => Access::ROLE_CONTRIB], 'archive contributor')

                ->canSeeNone()
                ->canEditNone()
                ->canCreateNone()

            ->whenIAm([], ['pending' => Access::ROLE_CONTRIB], 'pending contributor')

                ->canSeeNone()
                ->canEditNone()
                ->canCreateNone()
        ;
    }

    public function testContributorCanEditHisOwnContent()
    {
        $this->whenIAm([], ['off' => Access::ROLE_CONTRIB], 'off contributor');
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
            ->whenIAm([], [], 'authenticated with no rights')
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

        $this->getSiteManager()->dropContext();
    }

    public function testNoRoleAuthRightsOnDisabled()
    {
        $this->getSiteManager()->setContext($this->getSite('off'));

        $this
            ->whenIAm([], [], 'authenticated with no rights')
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
