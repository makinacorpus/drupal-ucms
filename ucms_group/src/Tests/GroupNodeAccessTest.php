<?php

namespace MakinaCorpus\Ucms\Group\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\Tests\NodeAccessTestTrait;

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
        $this->getNodeHelper()->resetCache();

        $instance = $this->getGroup($group);
        $this->getGroupManager()->getAccess()->addMember($instance->getId(), $this->contextualAccount->id());

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sites['no_on']     = $this->createDrupalSite(SiteState::ON);
        $this->sites['no_off']    = $this->createDrupalSite(SiteState::OFF);
        $this->sites['on']        = $this->createDrupalSite(SiteState::ON);
        $this->sites['off']       = $this->createDrupalSite(SiteState::OFF);

        // Create false set of nodes, a lot of them.
        $this->nodes['nogroup_site_on_published']   = $this->createDrupalNode(1, 'no_on', [], false, false, false);
        $this->nodes['nogroup_site_on_unpublished'] = $this->createDrupalNode(0, 'no_on', [], false, false, false);
        $this->nodes['nogroup_off_published']       = $this->createDrupalNode(1, 'no_off', [], false, false, true);
        $this->nodes['nogroup_off_unpublished']     = $this->createDrupalNode(0, 'no_off', [], false, false, true);
        $this->nodes['group_a_site_on_published']   = $this->createDrupalNode(1, 'on', [], false, false, false);
        $this->nodes['group_a_site_on_unpublished'] = $this->createDrupalNode(0, 'on', [], false, false, false);
        $this->nodes['group_b_site_on_published']   = $this->createDrupalNode(1, 'on', [], false, false, true);
        $this->nodes['group_b_site_on_unpublished'] = $this->createDrupalNode(0, 'on', [], false, false, true);
        $this->nodes['group_a_off_published']       = $this->createDrupalNode(1, 'off', [], false, false, true);
        $this->nodes['group_a_off_unpublished']     = $this->createDrupalNode(0, 'off', [], false, false, true);
        $this->nodes['group_b_off_published']       = $this->createDrupalNode(1, 'off', [], false, false, true);
        $this->nodes['group_b_off_unpublished']     = $this->createDrupalNode(0, 'off', [], false, false, true);

        // This'll be useful for debug, no business meaning.
        foreach ($this->nodes as $index => $node) {
            $node->setTitle($index);
        }

        $this->groups['a'] = $groupA = new Group();
        $groupA->setTitle('a');
        $this->groups['b'] = $groupB = new Group();
        $groupB->setTitle('b');

        $manager = $this->getGroupManager();
        $manager->getStorage()->save($groupA);
        $manager->getStorage()->save($groupB);

        $manager->getAccess()->addSite($groupA->getId(), $this->sites['on']->getId());
        $manager->getAccess()->addSite($groupB->getId(), $this->sites['off']->getId());

        $this->getSiteManager()->dropContext();
    }

    public function testGroupUserCanSeeOnlyHisGroup()
    {
        $this
            ->whenIAmMember('a', [], [])
                ->canSeeOnly([
                    'group_a_site_on_published',
                ])
                ->canEditNone()
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
    }

    public function testNoGroupUserCanSeeNothingElseThanGlobal()
    {
        
    }
}
