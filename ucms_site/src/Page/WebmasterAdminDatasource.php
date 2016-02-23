<?php


namespace MakinaCorpus\Ucms\Site\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Page\AbstractDatasource;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\SortManager;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\SiteManager;


class WebmasterAdminDatasource extends AbstractDatasource
{
    use StringTranslationTrait;


    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * @var SiteManager
     */
    private $manager;


    /**
     * Default constructor
     *
     * @param \DatabaseConnection $db
     * @param SiteManager $manager
     */
    public function __construct(\DatabaseConnection $db, SiteManager $manager)
    {
        $this->db = $db;
        $this->manager = $manager;
    }


    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [
            (new LinksFilterDisplay('role', $this->t("Role")))->setChoicesMap([
                Access::ROLE_WEBMASTER  => $this->t("Webmaster"),
                Access::ROLE_CONTRIB    => $this->t("Contributor"),
            ]),
        ];
    }


    /**
     * {@inheritdoc}
     */
    public function getItems($query, $sortField = null, $sortOrder = SortManager::DESC)
    {
        if (empty($query['site_id'])) {
            return [];
        }

        $site   = $this->manager->getStorage()->findOne($query['site_id']);
        $page   = pager_find_page();
        $limit  = 12;

        if (!empty($query['role'])) {
            $total = $this->manager->getAccess()->countUsersWithRole($site, $query['role']);
            $accessRecords = $this->manager->getAccess()->listUsersWithRole($site, $query['role'], $limit, $page * $limit);
        } else {
            $total = $this->manager->getAccess()->countUsersWithRole($site);
            $accessRecords = $this->manager->getAccess()->listUsersWithRole($site, null, $limit, $page * $limit);
        }

        pager_default_initialize($total, $limit);

        return $accessRecords;
    }


    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }
 }
