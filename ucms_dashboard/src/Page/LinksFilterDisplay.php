<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * Default implementation that will convert a single hashmap to a set of links
 */
class LinksFilterDisplay
{
    /**
     * @var string[]
     */
    private $choicesMap = [];

    /**
     * @var string
     */
    private $queryParameter;

    /**
     * @var string
     */
    private $title;

    /**
     * Default constructor
     *
     * @param string $queryParameter
     *   Query parameter name
     * @param string $title
     *   $title
     */
    public function __construct($queryParameter, $title = null)
    {
        $this->queryParameter = $queryParameter;
        $this->title = $title;
    }

    /**
     * Set choices map
     *
     * Choice map is a key-value array in which keys are indexed values and
     * values are human readable names that will supplant the indexed values
     * for end-user display, this has no effect on the query.
     *
     * @param string[] $choicesMap
     *   Keys are filter value, values are human readable labels
     *
     * @return LinksFilterDisplay
     */
    public function setChoicesMap($choicesMap)
    {
        $this->choicesMap = $choicesMap;

        return $this;
    }

    /**
     * {inheritdoc}
     */
    public function getTitle()
    {
        if (!$this->title) {
            return $this->queryParameter;
        }

        return $this->title;
    }

    /**
     * Get selected values from query
     *
     * @param string[] $query
     *
     * @return string[]
     */
    protected function getSelectedValues($query)
    {
        $values = [];

        if (isset($query[$this->queryParameter])) {

            $values = $query[$this->queryParameter];

            if (!is_array($values)) {
                $values = [$values];
            }
        }

        return $values;
    }

    /**
     * Get query parameters for a singe link
     *
     * @todo
     *   Handle possible multiple values
     *
     * @param string[] $query
     */
    protected function getParametersForLink($query, $value, $isActive = false)
    {
        if ($isActive) {
            unset($query[$this->queryParameter]);
            return $query;
        } else {
            return [$this->queryParameter => $value] + $query;
        }
    }

    /**
     * {inheritdoc}
     */
    public function build($query)
    {
        $links = [];
        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {

            $link = [
                'href'  => current_path(),
                'title' => filter_xss($label),
                'html'  => true,
            ];

            if (in_array($value, $selectedValues)) {
                $link['attributes']['class'][] = 'active';
                $link['query'] = $this->getParametersForLink($query, $value, true);
            } else {
                $link['query'] = $this->getParametersForLink($query, $value);
            }

            $links[$value] = $link;
        }

        if (empty($links)) {
            // This is no links to display, do not leave an empty title
            $links['_empty'] = [
                'title'       => t("No values"),
                'href'        => current_path(),
                'query'       => $query,
                'attributes'  => ['class' => ['disabled']],
            ];
        }

        // Forces the l() function to skip the 'active' class by adding empty
        // attributes array and settings a stupid language onto the link (this
        // is Drupal 7 specific and exploit a Drupal weird behavior)
        foreach ($links as &$link) {
            if (empty($link['attributes'])) {
                $link['attributes'] = [];
            }
            $link['language'] = (object)['language' => LANGUAGE_NONE];
        }

        return [
            '#theme'    => 'links__ucms__dashboard__filter',
            '#heading'  => $this->getTitle(),
            '#links'    => $links,
        ];
    }
}

