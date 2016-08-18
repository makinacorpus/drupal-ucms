<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\UserAccess;

use Symfony\Component\HttpFoundation\JsonResponse;

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
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
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
     * User autocomplete callback
     */
    public function userAutocompleteAction($string)
    {
        $manager = $this->getSiteManager();
        $account = $this->getCurrentUser();

        if (!$manager->getAccess()->userIsWebmaster($account) && !$account->hasPermission(UserAccess::PERM_MANAGE_ALL)) {
            throw $this->createAccessDeniedException();
        }

        $database = $this->getDatabaseConnection();
        $q = $database
            ->select('users', 'u')
            ->fields('u', ['uid', 'name'])
            ->condition('u.name', '%' . $database->escapeLike($string) . '%', 'LIKE')
            ->condition('u.uid', [0, 1], 'NOT IN')
            ->orderBy('u.name', 'asc')
            ->range(0, 16)
        ;

        $suggest = [];

        foreach ($q->execute()->fetchAll() as $record) {
            $key = $record->name . ' [' . $record->uid . ']';
            $suggest[$key] = check_plain($record->name);
        }

        return new JsonResponse($suggest);
    }

    public function siteAutocompleteAction($string)
    {
        $manager = $this->getSiteManager();
        $account = $this->getCurrentUser();

        if (!$manager->getAccess()->userIsWebmaster($account) && !$account->hasPermission(Access::PERM_SITE_VIEW_ALL)) {
            throw $this->createAccessDeniedException();
        }

        $database = $this->getDatabaseConnection();
        $q = $database
            ->select('ucms_site', 's')
            ->fields('s', ['id', 'title_admin'])
            ->condition(
                (new \DatabaseCondition('OR'))
                    ->condition('s.title', '%' . $database->escapeLike($string) . '%', 'LIKE')
                    ->condition('s.title_admin', '%' . $database->escapeLike($string) . '%', 'LIKE')
                    ->condition('s.http_host', '%' . $database->escapeLike($string) . '%', 'LIKE')
            )
            ->orderBy('s.title_admin', 'asc')
            ->range(0, 16)
        ;

        $suggest = [];

        foreach ($q->execute()->fetchAll() as $record) {
            $key = $record->title_admin . ' [' . $record->id . ']';
            $suggest[$key] = check_plain($record->title_admin);
        }

        return new JsonResponse($suggest);
    }
}
