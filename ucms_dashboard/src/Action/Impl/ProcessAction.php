<?php

namespace MakinaCorpus\Ucms\Dashboard\Action\Impl;

use Drupal\Core\Url;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractAction;
use MakinaCorpus\Ucms\Dashboard\Action\ItemIdentity;

/**
 * Process action allows the developer to give a callback or a closure to do
 * whatever the action does. Following this:
 *
 *  - this module will provide the complete UI for processing the action,
 *  - this module will provide the confirmation forms for processing the action,
 *  - complete action implementation will remain in the provider.
 *
 * For this to work, you need to ensure an item loader exists for the given item.
 */
final class ProcessAction extends AbstractAction
{
    private $identity;
    private $processCallback;

    /**
     * Create instance from array
     */
    public static function create(string $id, ItemIdentity $identity, callable $processCallback, array $options): ProcessAction
    {
        $instance = new self();
        self::populate($instance, $id, $options);

        $instance->identity = $identity;
        $instance->processCallback = $processCallback;

        return $instance;
    }

    /**
     * Explicit new is disallowed from the outside world.
     */
    protected function __construct()
    {
    }

    public function getDrupalUrl(): Url
    {
        return new Url('ucms_dashboard.action.process', [
            'action' => $this->getId(),
            'type' => $this->identity->type,
            'id' => $this->identity->id,
        ]);
    }

    public function getIdentity(): ItemIdentity
    {
        return $this->identity;
    }

    /**
     * Run processing
     */
    public function process()
    {
        return \call_user_func($this->processCallback);
    }
}
