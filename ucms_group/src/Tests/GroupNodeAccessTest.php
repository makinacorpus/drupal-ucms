<?php

namespace MakinaCorpus\Ucms\Group\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\Tests\NodeAccessTestTrait;
use MakinaCorpus\Ucms\Site\Access;

/**
 * Please note that there is actually not much to test, since that the global
 * NodeAccessTest from 'ucms_site' will do it for us: if it detects that the
 * 'ucms_group' module is enable, it will implictely use the meta group to
 * attach everything (sites, nodes, users) onto and test within the group
 * transparently: this will validate pretty much everything. Only stuff we do
 * have to test here is the fact the users can NOT see other groups' content.
 */
class GroupNodeAccessTest extends AbstractDrupalTest
{
    use NodeAccessTestTrait;

    private $groups = [];

    /**
     * @return GroupManager
     */
    private function getGroupManager()
    {
        return $this->getDrupalContainer()->get('ucms_group.manager');
    }

    /**
     * @return Group
     */
    protected function getGroup($name)
    {
        if (!isset($this->groups[$name])) {
            throw new \Exception(sprintf("%s: no such group", $name));
        }
        return $this->groups[$name];
    }

    /**
     * @return $this
     */
    protected function whenIAmMember($group, $permissionList = [], $siteMap = [])
    {
        $this->contextualAccount = $this->createDrupalUser($permissionList, $siteMap);

        $instance = $this->getGroup($group);
        $this->getGroupManager()->getAccess()->addMember($instance->getId(), $this->contextualAccount->id());

        return $this;
    }

    protected function tearDown()
    {
        foreach ($this->groups as $group) {
            $this->getGroupManager()->getStorage()->delete($group);
        }

        $this->eraseAll();

        parent::tearDown();
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        if (!$this->moduleExists('ucms_group')) {
            $this->markTestSkipped("You must enable the ucms_group module to run this test");
            return;
        }

        $this->sites['no_on']     = $this->createDrupalSite(SiteState::ON);
        $this->sites['no_off']    = $this->createDrupalSite(SiteState::OFF);
        $this->sites['on']        = $this->createDrupalSite(SiteState::ON);
        $this->sites['off']       = $this->createDrupalSite(SiteState::OFF);

        // Create groups
        $this->groups['a'] = $groupA = new Group();
        $groupA->setTitle('a');
        $this->groups['b'] = $groupB = new Group();
        $groupB->setTitle('b');
        // Save groups
        $manager = $this->getGroupManager();
        $manager->getStorage()->save($groupA);
        $manager->getStorage()->save($groupB);
        // Set sites in groups
        $manager->getAccess()->addSite($groupA->getId(), $this->sites['on']->getId(), true);
        $manager->getAccess()->addSite($groupB->getId(), $this->sites['off']->getId(), true);

        // Create false set of nodes, a lot of them.
        $this->nodes['nogroup_site_on_published']   = $this->createDrupalNode(1, 'no_on', [], false, false, false);
        $this->nodes['nogroup_site_on_unpublished'] = $this->createDrupalNode(0, 'no_on', [], false, false, false);
        $this->nodes['nogroup_off_published']       = $this->createDrupalNode(1, 'no_off', [], false, false, true);
        $this->nodes['nogroup_off_unpublished']     = $this->createDrupalNode(0, 'no_off', [], false, false, true);
        $this->nodes['group_a_site_on_published']   = $this->createDrupalNode(1, 'on', [], false, false, false, ['group_id' => $groupA->getId()]);
        $this->nodes['group_a_group_published']     = $this->createDrupalNode(1, null, [], true, true, false, ['group_id' => $groupA->getId()]);
        $this->nodes['group_a_site_on_unpublished'] = $this->createDrupalNode(0, 'on', [], false, false, false, ['group_id' => $groupA->getId()]);
        $this->nodes['group_b_site_on_published']   = $this->createDrupalNode(1, 'on', [], false, false, true, ['group_id' => $groupB->getId()]);
        $this->nodes['group_b_site_on_unpublished'] = $this->createDrupalNode(0, 'on', [], false, false, true, ['group_id' => $groupB->getId()]);
        $this->nodes['group_b_group_published']     = $this->createDrupalNode(1, null, [], true, true, false, ['group_id' => $groupB->getId()]);
        $this->nodes['group_a_off_ghost_published'] = $this->createDrupalNode(1, 'off', [], false, false, true, ['group_id' => $groupA->getId(), 'is_ghost' => 1]);
        $this->nodes['group_a_off_published']       = $this->createDrupalNode(1, 'off', [], false, false, true, ['group_id' => $groupA->getId()]);
        $this->nodes['group_b_off_ghost_published'] = $this->createDrupalNode(1, 'off', [], false, false, true, ['group_id' => $groupB->getId(), 'is_ghost' => 1]);
        $this->nodes['group_b_off_published']       = $this->createDrupalNode(1, 'off', [], false, false, true, ['group_id' => $groupB->getId()]);
        $this->nodes['group_a_global_ghost']        = $this->createDrupalNode(1, null, [], true, false, true, ['group_id' => $groupA->getId(), 'is_ghost' => 1]);
        $this->nodes['group_b_global_ghost']        = $this->createDrupalNode(1, null, [], true, false, true, ['group_id' => $groupB->getId(), 'is_ghost' => 1]);

        // This'll be useful for debug, no business meaning.
        foreach ($this->nodes as $index => $node) {
            $node->setTitle($index);
        }

        $this->getSiteManager()->dropContext();
        $this->getGroupManager()->getAccess()->resetCache();
    }

    public function testPrettyMuchEverything()
    {
        // Group member, no site, should only site global published content
        $this
            ->whenIAmMember('a', [Access::PERM_CONTENT_VIEW_GLOBAL, Access::PERM_CONTENT_VIEW_GROUP], [])
                ->canSeeOnly([
                    'group_a_global_ghost',
                    'group_a_group_published',
                ])
                ->canEditNone()
                ->canCreateNone()
        ;

        // Group member a and b, no site, write on global, can see and write global
        $this
            ->whenIAmMember('a', [Access::PERM_CONTENT_MANAGE_GLOBAL, Access::PERM_CONTENT_VIEW_GROUP], [])
                ->canSeeOnly([
                    'group_a_group_published',
                    //'group_b_group_published',
                    // @todo Why a group user could not see global platform content?
                    'group_a_global_ghost',
                ])
                ->canEditOnly([
                    'group_a_group_published',
                    'group_a_global_ghost',
                ])
                ->canCreate('page')
        ;

        // Group member, webmaster, should see all site content and group content
        $this
            ->whenIAmMember('a', [Access::PERM_CONTENT_VIEW_GLOBAL, Access::PERM_CONTENT_VIEW_GROUP], ['on' => Access::ROLE_WEBMASTER])
                ->canSeeOnly([
                    'group_a_site_on_published',
                    'group_a_group_published',
                    'group_a_site_on_unpublished',
                    'group_b_site_on_published',
                    'group_b_site_on_unpublished',
                    'group_a_global_ghost',
                ])
                ->canEditOnly([
                    'group_a_site_on_published',
                    'group_a_site_on_unpublished',
                    'group_b_site_on_published',
                    'group_b_site_on_unpublished',
                ])
                // @todo why does this fail?
                // ->canCreate('page')
        ;

        // Group member, no global permission, can only see site content
        $this
            ->whenIAmMember('a', [], ['on' => Access::ROLE_WEBMASTER])
                ->canSeeOnly([
                    'group_a_site_on_published',
                    'group_a_site_on_unpublished',
                    'group_b_site_on_published',
                    'group_b_site_on_unpublished',
                ])
                ->canEditOnly([
                    'group_a_site_on_published',
                    'group_a_site_on_unpublished',
                    'group_b_site_on_published',
                    'group_b_site_on_unpublished',
                ])
                //->canCreate('page')
        ;

        // No group, can see group content but no ghosts
        // This does not tests it fully, but it validates that normal rights are not changed
        $this
            ->whenIAm([Access::PERM_CONTENT_VIEW_GLOBAL, Access::PERM_CONTENT_VIEW_GROUP], ['no_on' => Access::ROLE_WEBMASTER, 'no_off' => Access::ROLE_CONTRIB])
                ->canSeeOnly([
                    'nogroup_site_on_published',
                    'nogroup_site_on_unpublished',
                    'nogroup_off_published',
                    'nogroup_off_unpublished',
                    'group_a_group_published',
                    'group_b_group_published',
                ])
                ->canEditOnly([
                    'nogroup_site_on_published',
                    'nogroup_site_on_unpublished',
                ])
                //->canCreate('page')
        ;
    }
}
