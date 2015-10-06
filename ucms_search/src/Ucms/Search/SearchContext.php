<?php

namespace Ucms\Search;

class SearchContext
{
    /**
     * @var \Ucms\Search\Search
     */
    protected $search;

    /**
     * @var array
     */
    protected $facets = [];

    /**
     * Default constructor
     *
     * @param \Ucms\Search\Search
     */
    public function __construct(Search $search)
    {
        $this->search = $search;
    }

    /**
     * Register facet
     *
     * @param string $field
     *   The field name to filter
     * @param string[] $options
     *   Key value mapping of raw field values to human name for display
     * @param string $field
     *   Field name if different from the name
     *
     * @return \Ucms\Search\SearchContext
     */
    public function registerFacet($name, $options, $field = null)
    {
        $this->facets[$name] = [
            'field'   => $field,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Prepare and return the associated search object
     *
     * @param array $params
     *   Parameters are the HTTP GET parameters context
     *
     * @return \Ucms\Search\Search
     */
    public function prepareSearch($query)
    {
        // Apply facets to the current search
        foreach ($this->facets as $name => $data) {
            if (isset($query[$name])) {
                $value = $query[$name];
            } else {
                $value = null;
            }
            $this->search->addTermAggregation($name, $value, $data['field']);
        }

        return $this->search;
    }
}
