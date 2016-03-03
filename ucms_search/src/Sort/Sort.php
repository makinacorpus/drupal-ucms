<?php

namespace MakinaCorpus\Ucms\Search\Sort;

class Sort
{

    const MODE_MIN      = 'min';
    const MODE_MAX      = 'max';
    const MODE_AVG      = 'avg';
    const MODE_SUM      = 'sum';
    const MODE_MED      = 'median';

    const ORDER_ASC     = 'asc';
    const ORDER_DESC    = 'desc';

    const MISSING_FIRST = '_first';
    const MISSING_LAST  = '_last';


    protected $field;

    protected $order;

    protected $mode;

    protected $missing;


    /**
     * Sort constructor.
     *
     * @param $field
     * @param string $order
     */
    public function __construct($field, $order = self::ORDER_ASC)
    {
        $this->field = $field;
        $this->order = $order;
    }


    /**
     * Get sort field.
     *
     * @return mixed
     */
    public function getField()
    {
        return $this->field;
    }


    /**
     * Get sort order.
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }


    /**
     * Set sort mode for array or multi-valued fields.
     *
     * @param $mode
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Get sort mode for array or multi-valued fields.
     *
     * @return string
     */
    public function getMode()
    {
        return $this->mode;
    }


    /**
     * Set missing field behavior.
     *
     * @param $missing
     */
    public function setMissing($missing)
    {
        $this->missing = $missing;
    }

    /**
     * Get missing field behavior.
     *
     * @return string
     */
    public function getMissing()
    {
        return $this->missing;
    }


    /**
     * Get sort structure.
     *
     * @return array
     */
    public function getSortStructure()
    {
        $sort = [$this->getField() => ['order' => $this->getOrder()]];

        if ($this->getMode()) {
            $sort[$this->getField()]['mode'] = $this->getMode();
        }
        if ($this->getMissing()) {
            $sort[$this->getField()]['missing'] = $this->getMissing();
        }

        return $sort;
    }

}
