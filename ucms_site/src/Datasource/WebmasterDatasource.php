<?php

namespace MakinaCorpus\Ucms\Site\Datasource;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
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
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(Connection $database, SiteManager $siteManager)
    {
        $this->database = $database;
        $this->siteManager = $siteManager;
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
    public function supportsPagination(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsFulltextSearch(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        $ret = [];

        $ret[] = new Filter('site_id');
        // @todo collect site for relative to site roles
        $ret[] = (new Filter('role', $this->t("Role")))->setChoicesMap($this->siteManager->getAccess()->getDefaultSiteRoles());

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(): array
    {
        return [
            'u.name'  => $this->t("Name"),
            'u.mail'  => $this->t("Email"),
            'sa.role' => $this->t("Role"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResultInterface
    {
        if (!$query->has('site_id')) {
            return $this->createEmptyResult();
        }

        $site = $this->siteManager->getStorage()->findOne($query->get('site_id'));

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

        if ($query->has('role')) {
            if (\is_array($value = $query->get('role'))) {
                $select->condition('sa.role', $value, 'IN');
            } else {
                $select->condition('sa.role', $value);
            }
        }

        $select->join('users_field_data', 'u', "u.uid = sa.uid");

        if ($search = $query->getRawSearchString()) {
            $escaped = $this->database->escapeLike($search);
            $select->condition(
                (new Condition('OR'))
                  ->condition('u.name', '%'.$escaped.'%', 'LIKE')
                  ->condition('u.mail', '%'.$escaped.'%', 'LIKE')
            );
        }
        $total = $select->countQuery()->execute()->fetchField();
        if (!$total) {
            return $this->createEmptyResult();
        }

        if ($query->hasSortField()) {
            $select->orderBy($query->getSortField(), Query::SORT_DESC === $query->getSortOrder() ? 'desc' : 'asc');
        }

        /** @var \PDOStatement $result */
        $result = $select
            ->range($query->getOffset(), $query->getLimit())
            ->orderBy('sa.uid', $query->getSortOrder())
            ->execute()
        ;

        $result->setFetchMode(\PDO::FETCH_CLASS, SiteAccessRecord::class);

        return $this->createResult($result, $total);
    }
 }
