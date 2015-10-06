<?php

namespace Ucms\Search;

class Facet
{
    /**
     * @var string
     */
    private $title;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $operator;

    /**
     * @var string[]
     */
    private $selectedValues = [];

    /**
     * @var string[]
     */
    private $valueFilter = [];

    /**
     * @var string[]
     */
    private $choices = [];

    /**
     * @var string
     */
    private $choicesMap = [];

    /**
     * @var string
     */
    private $type = 'terms';

    public function __construct($field, $type = 'terms', $operator = Query::OP_AND)
    {
        $this->field = $field;
        $this->type = $type;
        $this->operator = $operator;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function getField()
    {
        return $this->field;
    }

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle()
    {
        if ($this->title) {
            return $this->title;
        } else {
            return $this->field;
        }
    }

    public function setSelectedValue($selectedValues = [])
    {
        $this->selectedValues = $selectedValues;

        return $this;
    }

    public function getSelectedValues()
    {
        return $this->selectedValues;
    }

    public function setValueFilter($valueFilter)
    {
        $this->valueFilter = $valueFilter;

        return $this;
    }

    public function getValueFilter()
    {
        return $this->valueFilter;
    }

    public function setChoicesMap($choicesMap)
    {
        $this->choicesMap = $choicesMap;

        return $this;
    }

    public function setChoices($choices)
    {
        $this->choices = $choices;

        return $this;
    }

    public function getFormattedChoices()
    {
        $ret = [];

        foreach ($this->choices as $value => $count) {
            if (isset($this->choicesMap[$value])) {
                $ret[$value] = $this->choicesMap[$value] . " (". $count .")";
            } else {
                $ret[$value] = $value . " (". $count .")";
            }
        }

        return $ret;
    }
}
