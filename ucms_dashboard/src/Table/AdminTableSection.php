<?php

namespace MakinaCorpus\Ucms\Dashboard\Table;

class AdminTableSection
{
    private $key;
    private $title;
    private $rows = [];

    public function __construct($title, $key = null)
    {
        $this->key = $key;
        $this->title = $title;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function hasRow($key)
    {
        return isset($this->rows[$key]);
    }

    public function getRowKey($label)
    {
        foreach ($this->rows as $key => $row) {
            if ($label === $row[0]) {
                return $key;
            }
        }

        return false;
    }

    public function removeRow($key)
    {
        unset($this->rows[$key]);

        return $this;
    }

    public function removeRowWithLabel($label)
    {
        $key = $this->getRowKey($label);

        if ($key) {
            $this->removeRow($key);
        }

        return $this;
    }

    public function addRow($label, $value, $key = null)
    {
        if ($key) {
            $this->rows[$key] = [$label, $value];
        } else {
            $this->rows[] = [$label, $value];
        }

        return $this;
    }

    public function getAllRows()
    {
        return $this->rows;
    }
}
