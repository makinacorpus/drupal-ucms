<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Sort manager, it worthes the shot to have a decicated class for this
 *
 * All constants are compatible with Drupal DBTNG sorts.
 */
class SortManager
{
    use StringTranslationTrait;

    /**
     * Descending order
     */
    const DESC = 'desc';

    /**
     * Ascending order
     */
    const ASC = 'asc';

    /**
     * @var string
     */
    private $paramField = 'st';

    /**
     * @var string
     */
    private $paramOrder = 'by';

    /**
     * @var string
     */
    private $defaultField = null;

    /**
     * @var string
     */
    private $defaultOrder = self::DESC;

    /**
     * @var string[]
     */
    private $allowed = [];

    /**
     * Default constructor
     *
     * @param string $paramField
     *   Sort field query parameter name
     * @param string $paramOrder
     *   Sort order query parameter name
     */
    public function __construct($paramField = null, $paramOrder = null)
    {
        if ($paramField) {
            $this->paramField = $paramField;
        }
        if ($paramOrder) {
            $this->paramOrder = $paramOrder;
        }
    }

    /**
     * Set default sort
     *
     * @param string $field
     * @param string $order
     */
    public function setDefault($field, $order = self::DESC)
    {
        if (!isset($this->allowed[$field])) {
            trigger_error(sprintf("%s field is not allowed for sorting", $field), E_USER_ERROR);
            return;
        }
        if (!in_array($order, [self::ASC, self::DESC])) {
            trigger_error(sprintf("%s order is not allowed for sorting", $order), E_USER_ERROR);
            return;
        }

        $this->defaultField = $field;
        $this->defaultOrder = $order;
    }

    /**
     * Set available fields
     *
     * @param string[] $allowed
     *   Keys are field names values are human readable names
     */
    public function setFields(array $allowed)
    {
        $this->allowed = $allowed;

        if (!$this->defaultField) {
            $this->defaultField = key($this->allowed);
        }
    }

    /**
     * Get current page sort field
     *
     * @param string[] $query
     *
     * @return string
     */
    public function getCurrentField($query)
    {
        if (isset($query[$this->paramField])) {
            $field = $query[$this->paramField];
            if ($this->allowed[$field]) {
                return $field;
            }
        }
        return $this->defaultField;
    }

    /**
     * Get current page sort order
     *
     * @param string[] $query
     *
     * @return string
     */
    public function getCurrentOrder($query)
    {
        if (isset($query[$this->paramOrder])) {
            $order = $query[$this->paramOrder];
            if (in_array($order, [self::ASC, self::DESC])) {
                return $order;
            }
        }
        return $this->defaultOrder;
    }

    private function buildLink($query, $route, $param, $value, $label, $current, $default)
    {
        $link = [
            'href'        => $route,
            'title'       => $label,
            'html'        => true,
            'attributes'  => [],
            // Forces the l() function to skip the 'active' class by adding empty
            // attributes array and settings a stupid language onto the link (this
            // is Drupal 7 specific and exploit a Drupal weird behavior)
            'language'    => (object)['language' => 'und'],
        ];

        if ($value === $default) {
            $link['query'] = array_filter([$param => null] + $query); 
        } else {
            $link['query'] = [$param => $value] + $query;
        }
        if ($value === $current) {
            $link['attributes']['class'][] = 'active';
        }

        return $link;
    }

    /**
     * Build field links
     *
     * @param string[] $query
     * @param string $href
     *
     * @return mixed
     *   drupal_render() friendly structure
     */
    public function buildFieldLinks($query, $route)
    {
        $links = [];

        $current = $this->getCurrentField($query);

        foreach ($this->allowed as $value => $label) {
            $links[$value] = $this->buildLink($query, $route, $this->paramField, $value, $label, $current, $this->defaultField);
        }

        return [
            '#theme'    => 'links__ucms_dashboard_sort__field',
            '#heading'  => $this->t("Sort by"),
            '#links'    => $links,
        ];
    }

    /**
     * Build order links
     *
     * @param string[] $query
     * @param string $href
     *
     * @return mixed
     *   drupal_render() friendly structure
     */
    public function builOrderLinks($query, $route)
    {
        $links  = [];

        $current = $this->getCurrentOrder($query);
        $map = [self::ASC => $this->t("ascending"), self::DESC => $this->t("descending")];

        foreach ($map as $value => $label) {
            $links[$value] = $this->buildLink($query, $route, $this->paramOrder, $value, $label, $current, $this->defaultOrder);;
        }

        return [
            '#theme'    => 'links__ucms_dashboard_sort__order',
            '#heading'  => $this->t("Order by"),
            '#links'    => $links,
        ];
    } 
}
