<?php

namespace MakinaCorpus\Ucms\Tree\Datasource;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Calista\Datasource\AbstractDatasource;
use MakinaCorpus\Calista\Datasource\DatasourceResultInterface;
use MakinaCorpus\Calista\Query\Filter;
use MakinaCorpus\Calista\Query\Query;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\MenuStorageInterface;

final class MenuDatasource extends AbstractDatasource
{
    private $menuStorage;

    /**
     * Default constructor
     */
    public function __construct(MenuStorageInterface $menuStorage)
    {
        $this->menuStorage = $menuStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemClass(): string
    {
        return Menu::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new Filter('site_id'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getSorts(): array
    {
        return [
            'm.name' => new TranslatableMarkup("Name"),
            'm.title' => new TranslatableMarkup("Title"),
            'm.role' => new TranslatableMarkup("Role"),
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

        $conditions = ['site_id' => $query->get('site_id')];

        // @todo handle pagination and stuff
        return $this->createResult($this->menuStorage->loadWithConditions($conditions));
    }
}
