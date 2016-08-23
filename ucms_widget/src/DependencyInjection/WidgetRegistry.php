<?php

namespace MakinaCorpus\Ucms\Widget\DependencyInjection;

use MakinaCorpus\Ucms\Widget\NullWidget;
use MakinaCorpus\Ucms\Widget\WidgetInterface;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widget registry, keeps track of existing components
 */
class WidgetRegistry
{
    use ContainerAwareTrait;

    private $instances = [];
    private $services = [];
    private $debug = false;

    public function __construct(ContainerInterface $container, $debug = false)
    {
        $this->setContainer($container);
        $this->debug = $debug;
    }

    /**
     * @return boolean
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * Register a single instance
     *
     * @param string[] $map
     *   Keys are widget identifiers, values are service identifiers
     */
    public function registerAll($map)
    {
        foreach ($map as $type => $id) {
            if (isset($this->services[$type])) {
                if ($this->debug) {
                    trigger_error(sprintf("Widget type '%s' redefinition, ignoring", $type), E_USER_ERROR);
                }
            }
            $this->services[$type] = $id;
        }
    }

    /**
     * Get all register widget names
     *
     * @return string[][]
     *   Keys are types, values are names
     */
    public function getAllNames()
    {
        // @todo is it useful to set human readable names?
        return array_combine(array_keys($this->services), array_keys($this->services));
    }

    /**
     * Get instance
     *
     * @param string $type
     *
     * @return WidgetInterface
     */
    public function get($type)
    {
        if (!isset($this->instances[$type])) {

            if (!isset($this->services[$type])) {
                if ($this->debug) {
                    trigger_error(sprintf("Widget type '%s' does not exist, returning a null implementation", $type), E_USER_ERROR);
                }
                // This primarily meant to display stuff, we should never WSOD
                // in the user's face, return a null object instead that will
                // UI operations smooth and error tolerant.
                return new NullWidget();
            }

            $this->instances[$type] = $this->container->get($this->services[$type]);
        }

        return $this->instances[$type];
    }
}
