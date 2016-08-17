<?php

namespace MakinaCorpus\Ucms\Group\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Group\Form\GroupEdit;
use MakinaCorpus\Ucms\Group\Form\GroupMemberAddExisting;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;

use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller
{
    use PageControllerTrait;

    /**
     * @return GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('ucms_group.manager');
    }

    /**
     * @return DatasourceInterface
     */
    private function getGroupAdminDatasource()
    {
        return $this->get('ucms_group.admin.group_datasource');
    }

    /**
     * @return DatasourceInterface
     */
    private function getGroupSiteAdminDatasource()
    {
        return $this->get('ucms_group.admin.group_site_datasource');
    }

    /**
     * @return DatasourceInterface
     */
    private function getGroupMemberAdminDatasource()
    {
        return $this->get('ucms_group.admin.group_member_datasource');
    }

    /**
     * @return int
     */
    private function getCurrentUserId()
    {
        return $this->get('current_user')->id();
    }

    /**
     * View all groups action
     */
    public function viewAllAction(Request $request)
    {
        return $this
            ->createTemplatePage(
                $this->getGroupAdminDatasource(),
                'module:ucms_group:views/Page/groupAdmin.html.twig'
            )
            ->render($request->query->all())
        ;
    }

    /**
     * View my groups action
     */
    public function viewMineAction(Request $request)
    {
        return $this
            ->createTemplatePage(
                $this->getGroupAdminDatasource(),
                'module:ucms_group:views/Page/groupAdminMine.html.twig'
            )
            ->setBaseQuery([
                'uid' => $this->getCurrentUserId(),
            ])
            ->render($request->query->all())
        ;
    }

    /**
     * Group add action
     */
    public function addAction()
    {
        return \Drupal::formBuilder()->getForm(GroupEdit::class);
    }

    /**
     * Group edit action
     */
    public function editAction(Group $group)
    {
        return \Drupal::formBuilder()->getForm(GroupEdit::class, $group);
    }

    /**
     * Group details action
     */
    public function viewAction(Group $group)
    {
        throw new \Exception("Not implemented yet");
    }

    /**
     * View members action
     */
    public function memberListAction(Request $request, Group $group)
    {
        return $this
            ->createTemplatePage(
                $this->getGroupMemberAdminDatasource(),
                'module:ucms_group:views/Page/groupMemberAdmin.html.twig'
            )
            ->setBaseQuery([
                'group' => $group->getId(),
            ])
            ->render($request->query->all())
        ;
    }

    /**
     * Add existing member action
     */
    public function memberAddAction(Group $group)
    {
        return \Drupal::formBuilder()->getForm(GroupMemberAddExisting::class, $group);
    }

    /**
     * Site list action for group
     */
    public function siteListAction(Request $request)
    {
        return $this
            ->createTemplatePage(
                $this->getGroupSiteAdminDatasource(),
                'module:ucms_group:views/Page/groupSiteAdmin.html.twig'
            )
            ->setBaseQuery([
                'uid' => $this->getCurrentUserId(),
            ])
            ->render($request->query->all())
        ;
    }
}
