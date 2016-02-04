<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

interface FilterDisplayInterface
{
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get field name
     *
     * @return string
     */
    public function getField();

    /**
     * Get rendered filter
     *
     * If an empty value is returned, the filter won't be displayed
     *
     * @param string[] $query
     * @param string $route
     *
     * @return mixed
     *   drupal_render() friendly structure
     */
    public function build($query, $route);
}
