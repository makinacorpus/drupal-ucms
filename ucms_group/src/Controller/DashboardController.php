<?php

namespace MakinaCorpus\Ucms\Group\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Group\Form\GroupEdit;
use MakinaCorpus\Ucms\Group\Form\GroupMemberAddExisting;
use MakinaCorpus\Ucms\Group\Form\GroupSiteAdd;
use MakinaCorpus\Ucms\Group\Form\SiteGroupAttach;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Ucms\Site\Access;

class DashboardController extends Controller
{
    use PageControllerTrait;
    use StringTranslationTrait;

    /**
     * @return AccountInterface
     */
    private function getCurrentUser()
    {
        return $this->get('current_user');
    }

    /**
     * View all groups action
     */
    public function viewAllAction(Request $request)
    {
        return $this->renderPage('ucms_group.list', $request);
    }

    /**
     * View my groups action
     */
    public function viewMineAction(Request $request)
    {
        return $this->renderPage('ucms_group.list', $request, [
            'base_query' => [
                'uid' => $this->getCurrentUser()->id(),
            ]
        ]);
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
        $table = $this->createAdminTable('ucms_group');

        $table
            ->addHeader($this->t("Information"), 'basic')
            ->addRow($this->t("Title"), $group->getTitle())
            ->addRow($this->t("Identifier"), $group->getId())
        ;

        $this->addArbitraryAttributesToTable($table, $group->getAttributes());

        return $table->render();
    }

    /**
     * View members action
     */
    public function memberListAction(Request $request, Group $group)
    {
        return $this->renderPage('ucms_group.list_members', $request, [
            'base_query' => [
                'group' => $group->getId(),
            ]
        ]);
    }

    /**
     * Add existing member action
     */
    public function memberAddAction(Group $group)
    {
        return \Drupal::formBuilder()->getForm(GroupMemberAddExisting::class, $group);
    }

    /**
     * Add site action
     */
    public function siteAddAction(Group $group)
    {
        return \Drupal::formBuilder()->getForm(GroupSiteAdd::class, $group);
    }

    /**
     * Add site action
     */
    public function siteAttachAction(Site $site)
    {
        if (!$this->isGranted(Access::PERM_GROUP_MANAGE_ALL)) {
            throw $this->createAccessDeniedException();
        }

        return \Drupal::formBuilder()->getForm(SiteGroupAttach::class, $site);
    }

    /**
     * Site list action for group
     */
    public function siteListAction(Request $request, Group $group)
    {
        return $this->renderPage('ucms_group.list_by_site', $request, [
            'base_query' => [
                'group' => $group->getId(),
            ]
        ]);
    }
}
