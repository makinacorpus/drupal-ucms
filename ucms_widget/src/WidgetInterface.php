<?php

namespace MakinaCorpus\Ucms\Widget;

use Drupal\Core\Entity\EntityInterface;

use MakinaCorpus\Ucms\Site\Site;

/**
 * Common interface for all widgets that may be displayed in sites pages
 * layouts by the user
 */
interface WidgetInterface
{
    /**
     * Render the widget
     *
     * @param EntityInterface $entity
     * @param Site $site
     * @param mixed[] $options
     * @param mixed[] $formatterOptions
     *
     * @return mixed
     *   Anything that can be placed in a twig template or rendered via
     *   drupal_render()
     */
    public function render(EntityInterface $entity, Site $site, $options = [], $formatterOptions = []);

    /**
     * Get default options
     *
     * @return mixed[]
     */
    public function getDefaultOptions();

    /**
     * Get options form class, if relevant
     *
     * @param mixed[] $options
     *   Current options
     *
     * @return null|string|array
     *   If null is returned, this widget will not have an option form;
     *   if a string is returned, it must be a valid form class name;
     *   if an array is given, it must be a valid Drupal form array.
     */
    public function getOptionsForm($options = []);

    /**
     * Get default options
     *
     * @return mixed[]
     */
    public function getDefaultFormatterOptions();

    /**
     * Get options form class, if relevant
     *
     * @param mixed[] $options
     *   Current options
     *
     * @return null|string|array
     *   If null is returned, this widget will not have an option form;
     *   if a string is returned, it must be a valid form class name;
     *   if an array is given, it must be a valid Drupal form array.
     */
    public function getFormatterOptionsForm($options = []);
}
