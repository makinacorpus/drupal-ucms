<?php


namespace MakinaCorpus\Ucms\User\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\SiteAccessService;


class UserAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteAccessService
     */
    private $access;


    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteAccessService $access
     */
    public function __construct(\DatabaseConnection $db, SiteAccessService $access)
    {
        $this->db = $db;
        $this->access = $access;
    }


    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $roles = $this->access->getDrupalRoleList();
        foreach ($roles as $rid => $role) {
            if (in_array($rid, [DRUPAL_ANONYMOUS_RID, DRUPAL_AUTHENTICATED_RID])) {
                unset($roles[$rid]);
            }
        }

        $statuses = [
            1 => $this->t("Enabled"),
            0 => $this->t("Disabled"),
        ];

        return [
            (new LinksFilterDisplay('role', $this->t("Role")))->setChoicesMap($roles),
            (new LinksFilterDisplay('status', $this->t("Status")))->setChoicesMap($statuses),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [
            'u.mail'      => $this->t("email"),
            'u.name'      => $this->t("name"),
            'u.status'    => $this->t("status"),
            'u.created'   => $this->t("creation date"),
            'u.login'     => $this->t("last connection date"),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
        return ['u.login', SortManager::DESC];
    }


    /**
     * {@inheritdoc}
     */
    public function getItems($query, $sortField = null, $sortOrder = SortManager::DESC)
    {
        $limit = 24;

        $q = $this->db->select('users', 'u');

        if (isset($query['status'])) {
            $q->condition('u.status', $query['status']);
        }
        if (!empty($query['role'])) {
            $q->join('users_roles', 'ur', "u.uid = ur.uid");
            $q->condition('ur.rid', $query['role']);
        }

        if ($sortField) {
            $q->orderBy($sortField, SortManager::DESC === $sortOrder ? 'desc' : 'asc');
        }

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition('u.name', '%' . db_like($query[$sParam]) . '%', 'LIKE');
        }

        $idList = $q
            ->fields('u', ['uid'])
            ->condition('u.uid', 0, '!=')
            ->condition('u.uid', 1, '!=')
            ->extend('PagerDefault')
            ->limit($limit)
            ->execute()
            ->fetchCol();

        return \Drupal::service('entity.manager')->getStorage('user')->load($idList);
    }


    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
 }
