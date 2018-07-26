<?php

namespace MakinaCorpus\Ucms\Dashboard\Controller;

use MakinaCorpus\Ucms\Dashboard\Table\AdminTable;

trait PageControllerTrait
{
    /**
     * Create an admin table
     *
     * @param string $name
     *   Name will be the template suggestion, and the event name, where the
     *   event name will be admin:table:NAME
     */
    protected function createAdminTable(string $name, array $attributes = []): AdminTable
    {
        return new AdminTable($name, $attributes /*, $this->eventDispatcher */);
    }

    /**
     * Given some admin table, abitrary add a new section with attributes within
     */
    protected function addArbitraryAttributesToTable(AdminTable $table, array $attributes = [], string $title = null)
    {
        if (!$attributes) {
            return;
        }

        $table->addHeader($title ?? "Attributes", 'attributes');

        foreach ($attributes as $key => $value) {
            if (!\is_scalar($value)) {
                $value = '<pre>'.\json_encode($value, JSON_PRETTY_PRINT).'</pre>';
            }
            $table->addRow($key, $value, $key);
        }
    }
}
