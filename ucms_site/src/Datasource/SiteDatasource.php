<?php

namespace MakinaCorpus\Ucms\Site\Datasource;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Condition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

class SiteDatasource extends AbstractDatasource
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
        return Site::class;
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
        $states = SiteState::getList(SiteState::ARCHIVE);

        foreach ($states as $key => $label) {
            $states[$key] = $this->t($label);
        }

        return [
            (new Filter('uid')),
            (new Filter('state', $this->t("State")))->setChoicesMap($states),
            // @todo missing site type registry or variable somewhere
            (new Filter('theme', $this->t("Theme")))->setChoicesMap($this->manager->getAllowedThemesOptionList()),
            (new Filter('template', $this->t("Template")))->setChoicesMap($this->manager->getTemplateList()),
            (new Filter('other', $this->t("Other")))->setChoicesMap(['t' => "template"]),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(): array
    {
        return [
            's.id'          => $this->t("Identifier"),
            's.title'       => $this->t("Title"),
            's.title_admin' => $this->t("Administrative title"),
            's.http_host'   => $this->t("Hostname"),
            's.state'       => $this->t("State"),
            's.type'        => $this->t("Type"),
            's.ts_changed'  => $this->t("Lastest update date"),
            's.ts_created'  => $this->t("Creation date"),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getItems(Query $query): DatasourceResultInterface
    {
        $select = $this->database->select('ucms_site', 's')->fields('s');
        $select->addTag('ucms_site_access');
        $select->leftJoin('users', 'u', "u.uid = s.uid");
        $select->groupBy('s.id');

        if ($query->has('state')) {
            if (\is_array($value = $query->get('state'))) {
                $select->condition('s.state', $value, 'IN');
            } else {
                $select->condition('s.state', $value);
            }
        }
        if ($query->has('theme')) {
            if (\is_array($value = $query->get('theme'))) {
                $select->condition('s.theme', $value, 'IN');
            } else {
                $select->condition('s.theme', $value);
            }
        }
        if ($query->has('template')) {
            if (\is_array($value = $query->get('template'))) {
                $select->condition('s.template_id', $value, 'IN');
            } else {
                $select->condition('s.template_id', $value);
            }
        }
        if ($query->has('uid')) {
            $select->join('ucms_site_access', 'sa', "sa.site_id = s.id");
            $select->condition('sa.uid', $query->get('uid'));
        }

        // Quite ugly, but working as of now
        if ($query->has('other')) {
            foreach ((array)$query->get('other') as $value) {
                switch ($value) {
                    case 't':
                        $select->condition('s.is_template', 1);
                        break;
                }
            }
        }

        if ($query->hasSortField()) {
            $select->orderBy($query->getSortField(), Query::SORT_DESC === $query->getSortOrder() ? 'desc' : 'asc');
        }
        $select->orderBy('s.id', Query::SORT_DESC === $query->getSortOrder() ? 'desc' : 'asc');

        if ($search = $query->getRawSearchString()) {
            $escaped = $this->database->escapeLike($search);
            $select->condition(
                (new Condition('OR'))
                  ->condition('s.http_host', '%'.$escaped.'%', 'LIKE')
                  ->condition('s.title', '%'.$escaped.'%', 'LIKE')
                  ->condition('s.title_admin', '%'.$escaped.'%', 'LIKE')
            );
        }

        $countQuery = $select->countQuery();
        $total = $countQuery->execute()->fetchField();

        if (!$total) {
            return $this->createEmptyResult();
        }

        $result = $select
            ->range($query->getOffset(), $query->getLimit())
            ->execute()
            ->fetchAll(\PDO::FETCH_CLASS, Site::class)
        ;

        return $this->createResult($result, $total);
    }
 }
