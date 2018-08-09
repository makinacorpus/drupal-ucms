<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

final class ProcessAction extends AbstractAction
{
    private $processCallback;

    /**
     * Create instance from array
     */
    public static function create(string $id, callable $processCallback, array $options): RouteAction
    {
        $instance = new self();
        self::populate($instance, $id, $options);

        $instance->processCallback = $processCallback;

        return $instance;
    }

    /**
     * Explicit new is disallowed from the outside world.
     */
    protected function __construct()
    {
    }

    /**
     * Run processing
     */
    public function process()
    {
        return \call_user_func($this->processCallback);
    }
}
