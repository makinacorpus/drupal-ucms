<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * Base implementation which leaves null a few mathods
 */
abstract class AbstractDatasource implements DatasourceInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFilters($query)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields($query)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultSort()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function init($query)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function hasSearchForm()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFormParamName()
    {
    }
}
