<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

interface DisplayInterface
{
    /**
     * Set default mode
     *
     * @param string $mode
     *
     * @return AbstractDisplay
     */
    public function setDefaultMode($mode);

    /**
     * Get default mode
     *
     * If none set get the first one per default
     *
     * @return string
     */
    public function getDefaultMode();

    /**
     * Set query parameter name
     *
     * @param string $parameterName
     *
     * @return AbstractDisplay
     */
    public function setParameterName($parameterName);

    /**
     * Prepare instance from query
     *
     * @param string[] $query
     *
     * @return AbstractDisplay
     */
    public function prepareFromQuery($query);

    /**
     * Render view links
     *
     * @param string $route
     *
     * @return array
     *   drupal_render() friendly structure
     */
    public function renderLinks($route);

    /**
     * Render content (object must be prepared with query)
     *
     * @param mixed[] $items
     *
     * @return array
     *   drupal_render() friendly structure
     */
    public function render($items);
}
