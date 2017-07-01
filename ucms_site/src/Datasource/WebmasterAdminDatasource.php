<?php

namespace MakinaCorpus\Ucms\Site\Datasource;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\Filter;
use MakinaCorpus\Calista\Datasource\Query;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
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
    public function getItemClass()
    {
        return SiteAccessRecord::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        // @todo restore relative role filtering, the dynamic aspect is due
        //   to the fact that extranet sites might have different roles from
        //   the platform default hardcoded ones
        return [
            new Filter('site_id'),
        ];

        /*
        if (empty($query['site_id'])) {
            return [];
        }

        $site = $this->manager->getStorage()->findOne($query['site_id']);
        $relativeRoles = $this->manager->getAccess()->collectRelativeRoles($site);

        $choices = [];
        foreach ($relativeRoles as $rrid => $label) {
          $choices[$rrid] = $label;
        }

        return [(new Filter('role', $this->t("Role")))->setChoicesMap($choices)];
         */
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query)
    {
        if (!$query->has('site_id')) {
            return $this->createEmptyResult();
        }

        $site = $this->manager->getStorage()->findOne($query->get('site_id'));

        if ($query->has('role')) {
            $role = $query->get('role');
            $total = $this->manager->getAccess()->countUsersWithRole($site, $role);
            $items = $this->manager->getAccess()->listUsersWithRole($site, $role, $query->getLimit(), $query->getOffset());
        } else {
            $total = $this->manager->getAccess()->countUsersWithRole($site);
            $items = $this->manager->getAccess()->listUsersWithRole($site, null, $query->getLimit(), $query->getOffset());
        }

        return $this->createResult($items, $total);
    }
 }
