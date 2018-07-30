<?php

namespace MakinaCorpus\Ucms\Site\Datasource;

use Drupal\Core\Database\Connection;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Ucms\Site\SiteAccessRecord;
use MakinaCorpus\Ucms\Site\SiteManager;

class WebmasterDatasource extends AbstractDatasource
{
    use StringTranslationTrait;

    private $database;
    private $manager;

    /**
     * Default constructor
     */
    public function __construct(Connection $database, SiteManager $manager)
    {
        $this->database = $database;
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass(): string
    {
        return SiteAccessRecord::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        /*
          if (empty($query['site_id'])) {
              return [];
          }
          $site = $this->manager->getStorage()->findOne($query['site_id']);

          if ($site) {
              $relativeRoles = $this->manager->getAccess()->collectRelativeRoles($site);
          } else {
              $relativeRoles = [];
          }
  
          return [
              new Filter('site'),
              (new Filter('role'), $this->t("Role"))->setChoicesMap($relativeRoles),
          ];
         */

        return [new Filter('site_id')];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResultInterface
    {
        if (!$query->has('site_id')) {
            return $this->createEmptyResult();
        }

        $site = $this->manager->getStorage()->findOne($query->get('site_id'));

        /*
        if (!empty($query['role'])) {
            $total = $this->manager->getAccess()->countUsersWithRole($site, $query['role']);
            $accessRecords = $this->manager->getAccess()->listUsersWithRole($site, $query['role'], $pageState->getLimit(), $pageState->getOffset());
        } else {
            $total = $this->manager->getAccess()->countUsersWithRole($site);
            $accessRecords = $this->manager->getAccess()->listUsersWithRole($site, null, $pageState->getLimit(), $pageState->getOffset());
        }
         */

        $select = $this
            ->database
            ->select('ucms_site_access', 'sa')
            ->fields('sa')
            ->fields('u', ['name', 'mail', 'status'])
            ->condition('sa.site_id', $site->getId())
        ;

        // @todo
        //  - should we add an added date in the access table?
        //  - return a cursor instead ? with a count() method for paging

        if (false /* $role */) {
            $select->condition('sa.role', null /* $role */);
        }

        $select->join('users_field_data', 'u', "u.uid = sa.uid");

        $total = $select->countQuery()->execute()->fetchField();
        if (!$total) {
            return $this->createEmptyResult();
        }

        /** @var \PDOStatement $result */
        $result = $select
            ->range($query->getOffset(), $query->getLimit())
            ->orderBy('sa.uid')
            ->execute()
        ;

        $result->setFetchMode(\PDO::FETCH_CLASS, SiteAccessRecord::class);

        return $this->createResult($result, $total);
    }
 }
