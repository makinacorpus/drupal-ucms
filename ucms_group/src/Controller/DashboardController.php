<?php

namespace MakinaCorpus\Ucms\Group\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Group\Form\GroupEdit;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Group\Page\GroupAdminDisplay;

use Symfony\Component\HttpFoundation\Request;

class DashboardController extends Controller
{
    use PageControllerTrait;
    use StringTranslationTrait;

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
        return $this->get('ucms_group.admin.datasource');
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
            ->createPage(
                $this->getGroupAdminDatasource(),
                new GroupAdminDisplay(
                    $this->getGroupManager(),
                    $this->t("There is no groups yet.")
                )
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
            ->createPage(
                $this->getGroupAdminDatasource(),
                new GroupAdminDisplay(
                    $this->getGroupManager(),
                    $this->t("You are not member of any group.")
                )
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
}
