<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * Default implementation that will convert a single hashmap to a set of links
 */
class Filter implements \Countable
{
    const URL_VALUE_SEP = '|';

    use PrepareableTrait;

    /**
     * Sure ugly has hell, but right now I have no clean solution
     *
     * @param string[] $query
     *
     * @return string[]
     */
    static public function fixQuery(array $query)
    {
        foreach ($query as $key => $value) {
            if (false !== strpos($value, self::URL_VALUE_SEP)) {
                $query[$key] = explode(self::URL_VALUE_SEP, $value);
            }
        }
        return $query;
    }

    private $choicesMap = [];
    private $queryParameter;
    private $title;
    private $isSafe = false;

    /**
     * Default constructor
     *
     * @param string $queryParameter
     *   Query parameter name
     * @param string $title
     *   Filter title
     * @param boolean $isSafe
     *   Filter title is safe (does not need escaping)
     */
    public function __construct($queryParameter, $title = null, $isSafe = false)
    {
        $this->queryParameter = $queryParameter;
        $this->title = $title;
        $this->isSafe = $isSafe;
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
     * @return Filter
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
     * {@inheritdoc}
     */
    public function getField()
    {
        return $this->queryParameter;
    }

    /**
     * Get selected values from query
     *
     * @param string[] $query
     *
     * @return string[]
     */
    private function getSelectedValues($query)
    {
        $values = [];

        if (isset($query[$this->queryParameter])) {

            $values = $query[$this->queryParameter];

            if (!is_array($values)) {
                if (false !== strpos($values, self::URL_VALUE_SEP)) {
                    $values = explode(self::URL_VALUE_SEP, $values);
                } else {
                    $values = [$values];
                }
            }
        }

        return array_map('trim', $values);
    }

    /**
     * Get query parameters for a singe link
     *
     * @param string[] $query
     *   Contextual query that represents the current page state
     * @param string $value
     *   Value for the given link
     * @param boolean $remove
     *   Instead of adding the value, it must removed from the query
     *
     * @return string[]
     *   New query with value added or removed
     */
    private function getParametersForLink($query, $value, $remove = false)
    {
        if (isset($query[$this->queryParameter])) {
            $actual = explode(self::URL_VALUE_SEP, $query[$this->queryParameter]);
        } else {
            $actual = [];
        }

        if ($remove) {
            if (false !== ($pos = array_search($value, $actual))) {
                unset($actual[$pos]);
            }
        } else {
            if (false === array_search($value, $actual)) {
                $actual[] = $value;
            }
        }

        if (empty($actual)) {
            unset($query[$this->queryParameter]);
            return $query;
        } else {
            sort($actual);
            return [$this->queryParameter => implode(self::URL_VALUE_SEP, $actual)] + $query;
        }
    }

    /**
     * Get links
     *
     * @return Link[]
     */
    public function getLinks()
    {
        $ret = [];

        $route = $this->getRoute();
        $query = $this->getRouteParamaters();

        $selectedValues = $this->getSelectedValues($query);

        foreach ($this->choicesMap as $value => $label) {

            $isActive = in_array($value, $selectedValues);

            if ($isActive) {
                $linkQuery = $this->getParametersForLink($query, $value, true);
            } else {
                $linkQuery = $this->getParametersForLink($query, $value);
            }

            $ret[] = new Link($label, $route, $linkQuery, $isActive);
        }

        return $ret;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->choicesMap);
    }
}

