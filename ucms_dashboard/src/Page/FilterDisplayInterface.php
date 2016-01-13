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
     * Get rendered filter
     *
     * If an empty value is returned, the filter won't be displayed
     *
     * @return mixed
     *   drupal_render() friendly structure
     */
    public function render();
}
