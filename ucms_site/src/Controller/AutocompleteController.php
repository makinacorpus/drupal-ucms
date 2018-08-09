<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\UserAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Component\Utility\Html;

class AutocompleteController extends ControllerBase
{
    private $database;
    private $siteManager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('database')
        );
    }

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, Connection $database)
    {
        $this->database = $database;
        $this->siteManager = $siteManager;
    }

    /**
     * User autocomplete callback
     */
    public function userAutocomplete(Request $request)
    {
        if (!$input = $request->query->get('q')) {
            return new JsonResponse([]);
        }

        $account = $this->currentUser();

        // @todo security
        if (/*!$this->siteManager->getAccess()->userIsWebmaster($account) && !$account->hasPermission(UserAccess::PERM_MANAGE_ALL) && !$this->account->hasPermission(UserAccess::PERM_USER_GOD)*/ false) {
            throw new AccessDeniedHttpException();
        }

        $select = $this->database
            ->select('users_field_data', 'u')
            ->fields('u', ['uid', 'name'])
            ->condition('u.name', '%' . $this->database->escapeLike($input) . '%', 'LIKE')
            ->condition('u.uid', [0, 1], 'NOT IN')
            ->orderBy('u.name', 'asc')
            ->range(0, 16)
            ->addTag('ucms_user_access')
        ;

        $suggest = [];

        foreach ($select->execute()->fetchAll() as $record) {
            $name = trim(Html::decodeEntities(\strip_tags($record->name)));
            $value = $name.' ['.$record->uid.']';
            $suggest[] = ['value' => $value, 'label' => $name];
        }

        return new JsonResponse($suggest);
    }

    /*
    public function siteAutocompleteAction(Request $request, $string)
    {
        $account = $this->currentUser();

        if (!$this->siteManager->getAccess()->userIsWebmaster($account) &&
            !$account->hasPermission(Access::PERM_SITE_VIEW_ALL) &&
            !$account->hasPermission(Access::PERM_SITE_MANAGE_ALL) &&
            !$account->hasPermission(Access::PERM_SITE_GOD)
        ) {
            throw new AccessDeniedHttpException();
        }

        $q = $this->database
            ->select('ucms_site', 's')
            ->fields('s', ['id', 'title_admin'])
            ->condition(
                (new Condition('OR'))
                    ->condition('s.title', '%' . $this->database->escapeLike($string) . '%', 'LIKE')
                    ->condition('s.title_admin', '%' . $this->database->escapeLike($string) . '%', 'LIKE')
                    ->condition('s.http_host', '%' . $this->database->escapeLike($string) . '%', 'LIKE')
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
     */
}
