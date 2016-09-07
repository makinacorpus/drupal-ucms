<?php

namespace MakinaCorpus\Ucms\Group\Controller;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Ucms\Site\Site;

class AutocompleteController extends Controller
{
    /**
     * Get current logged in user
     *
     * @return AccountInterface
     */
    private function getCurrentUser()
    {
        return $this->get('current_user');
    }

    /**
     * Get site manager
     *
     * @return GroupManager
     */
    private function getGroupManager()
    {
        return $this->get('ucms_group.manager');
    }

    /**
     * Get database connection
     *
     * @return \DatabaseConnection
     */
    private function getDatabaseConnection()
    {
        return $this->get('database');
    }

    /**
     * Autocomplete action for adding sites into group
     */
    public function siteAddAutocompleteAction(Request $request, Group $group, $string)
    {
        $manager = $this->getGroupManager();
        $account = $this->getCurrentUser();

        if (!$manager->getAccess()->userCanManageSites($account, $group)) {
            throw $this->createAccessDeniedException();
        }

        $database = $this->getDatabaseConnection();
        $q = $database
            ->select('ucms_site', 's')
            ->fields('s', ['id', 'title_admin'])
            ->isNull('s.group_id')
            ->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('s.title', '%' . $database->escapeLike($string) . '%', 'LIKE')
                    ->condition('s.title_admin', '%' . $database->escapeLike($string) . '%', 'LIKE')
                    ->condition('s.http_host', '%' . $database->escapeLike($string) . '%', 'LIKE')
            )
            ->orderBy('s.title_admin', 'asc')
            ->groupBy('s.id')
            ->range(0, 16)
            ->addTag('ucms_site_access')
        ;

        $suggest = [];

        foreach ($q->execute()->fetchAll() as $record) {
            $key = $record->title_admin . ' [' . $record->id . ']';
            $suggest[$key] = check_plain($record->title_admin);
        }

        return new JsonResponse($suggest);
    }

    /**
     * Autocomplete action for attaching group to site
     */
    public function siteAttachAutocompleteAction(Request $request, Site $site, $string)
    {
        $manager = $this->getGroupManager();
        $account = $this->getCurrentUser();

        if (!$manager->getAccess()->userCanManageAll($account)) {
            throw $this->createAccessDeniedException();
        }

        $database = $this->getDatabaseConnection();
        $q = $database
            ->select('ucms_group', 'g')
            ->fields('g', ['id', 'title'])
            ->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('g.title', '%' . $database->escapeLike($string) . '%', 'LIKE')
            )
            ->orderBy('g.title', 'asc')
            ->groupBy('g.id')
            ->range(0, 16)
            ->addTag('ucms_group_access')
        ;

        $suggest = [];

        foreach ($q->execute()->fetchAll() as $record) {
            $key = $record->title . ' [' . $record->id . ']';
            $suggest[$key] = check_plain($record->title);
        }

        return new JsonResponse($suggest);
    }
}
