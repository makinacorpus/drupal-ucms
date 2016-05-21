<?php

namespace MakinaCorpus\Ucms\Drop;

class DropStatus
{
    private $isError = false;
    private $needsRepaint = false;
    private $repaintOnlyItem = false;
    private $message = '';

    /**
     * Default constructor
     *
     * @param string $message
     * @param boolean $isError
     * @param boolean $needsRepaint
     * @param boolean $repaintOnlyItem
     */
    public function __construct($message = '', $isError = false, $needsRepaint = false, $repaintOnlyItem = false)
    {
        $this->message = $message;
        $this->isError = $isError;
        $this->needsRepaint = $needsRepaint;
        $this->repaintOnlyItem = $repaintOnlyItem;
    }

    public function isError()
    {
        return $this->isError;
    }

    public function needsRepaint()
    {
        return $this->needsRepaint;
    }

    public function repaintOnlyItem()
    {
        return $this->repaintOnlyItem;
    }

    public function getMessage()
    {
        return $this->message;
    }
}
