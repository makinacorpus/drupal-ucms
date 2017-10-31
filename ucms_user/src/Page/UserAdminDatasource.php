<?php


namespace MakinaCorpus\Ucms\User\Page;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\PageState;
use MakinaCorpus\Ucms\Dashboard\Page\SearchForm;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\SiteAccessService;
use MakinaCorpus\Ucms\User\UserAccess;


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
     * @var EntityManager
     */
    private $entityManager;

    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteAccessService $access
     * @param EntityManager $entityManager
     */
    public function __construct(\DatabaseConnection $db, SiteAccessService $access, EntityManager $entityManager)
    {
        $this->db = $db;
        $this->access = $access;
        $this->entityManager = $entityManager;
    }


    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        $roles = $this->access->getDrupalRoleList(!user_access(UserAccess::PERM_MANAGE_ALL_ROLES));
        foreach ($this->access->getDefaultRelativeRoles() as $rid => $role) {
            $roles[$rid] = $role;
        }
        sort($roles, SORT_NATURAL);

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
    public function getItems($query, PageState $pageState)
    {
        $q = $this
            ->db
            ->select('users', 'u')
            ->addTag('ucms_user_access')
            ->fields('u', ['uid'])
        ;

        if (isset($query['status'])) {
            $q->condition('u.status', $query['status']);
        }
        if (!empty($query['role'])) {
            $q->join('users_roles', 'ur', "u.uid = ur.uid");
            $q->condition('ur.rid', $query['role']);
        }

        if ($pageState->hasSortField()) {
            $q->orderBy($pageState->getSortField(), $pageState->getSortOrder());
        }

        $sParam = SearchForm::DEFAULT_PARAM_NAME;
        if (!empty($query[$sParam])) {
            $q->condition('u.name', '%' . db_like($query[$sParam]) . '%', 'LIKE');
        }

        $idList = $q
            ->condition('u.uid', 0, '!=')
            ->condition('u.uid', 1, '!=')
            ->extend('PagerDefault')
            ->limit($pageState->getLimit())
            ->execute()
            ->fetchCol();

        return $this
            ->entityManager
            ->getStorage('user')
            ->loadMultiple($idList)
        ;
    }


    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return true;
    }
 }
