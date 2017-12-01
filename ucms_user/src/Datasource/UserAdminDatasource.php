<?php

namespace MakinaCorpus\Ucms\User\Datasource;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager;
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
    public function getFilters()
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
            (new Filter('role', $this->t("Role")))->setChoicesMap($roles),
            (new Filter('status', $this->t("Status")))->setChoicesMap($statuses),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts()
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
    public function getItems(Query $query)
    {
        $q = $this
            ->db
            ->select('users', 'u')
            ->fields('u', ['uid'])
        ;

        $q->addTag('ucms_user_access');

        if ($query->has('status')) {
            $q->condition('u.status', $query->get('status'));
        }
        if ($query->has('role')) {
            $q->join('users_roles', 'ur', "u.uid = ur.uid");
            $q->condition('ur.rid', $query->get('role'));
        }

        if ($query->hasSortField()) {
            $q->orderBy($query->getSortField(), $query->getSortOrder());
        }

        $search = $query->getSearchString();
        if ($search) {
            $q->condition('u.name', '%' . db_like($search) . '%', 'LIKE');
        }

        // Exclude admin and anonymous users
        $q->condition('u.uid', 0, '!=')->condition('u.uid', 1, '!=');

        /** @var \MakinaCorpus\Drupal\Calista\Datasource\QueryExtender\DrupalPager $pager */
        $pager = $q->extend(DrupalPager::class)->setDatasourceQuery($query);
        $idList = $pager->execute()->fetchCol();

        $items = $this->entityManager->getStorage('user')->loadMultiple($idList);

        return $this->createResult($items, $pager->getTotalCount());
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass()
    {
        return AccountInterface::class;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch()
    {
        return true;
    }
 }
