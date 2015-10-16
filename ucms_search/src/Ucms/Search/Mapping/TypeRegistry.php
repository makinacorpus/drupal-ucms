<?php

namespace Ucms\Search\Mapping;

class TypeRegistry
{
    /**
     * @var mixed[]
     */
    private $instances = [];

    /**
     * @var \Ucms\Search\Mapping\NullMapping
     */
    private $nullInstance;

    /**
     * Default constructor
     */
    protected function __construct()
    {
        $this->nullInstance = new NullType();
    }

    /**
     * Register all
     *
     * @param mixed[] $types
     *   Keys are type names, values are either class name or instances
     *
     * @return \Ucms\Search\Mapping\TypeRegistry
     */
    public function register($types)
    {
        foreach ($types as $type => $definition) {
            $this->register($type, $definition);
        }

        return $this;
    }

    /**
     * Register instance
     *
     * Please note that if you set the same type more than once, the last
     * caller will win over the others and overrides the type definition.
     *
     * @param string $type
     *   ElasticSearch field type
     * @param string|\Ucms\Search\Mapping\TypeInterface $definition
     *   Either the class name that will be instanciated later or the already
     *   instanciated TypeInterface object
     *
     * @return \Ucms\Search\Mapping\TypeRegistry
     */
    public function register($type, $definition)
    {
        $this->instances[$type] = $definition;

        return $this;
    }

    /**
     * Find converter for given ElasticSearch type
     *
     * @param string $type
     *
     * @return \Ucms\Search\Mapping\TypeInterface
     */
    public function find($type)
    {
        if (!isset($this->instances[$type])) {
            trigger_error(sprintf("type '%s' is unregistered", $type), E_USER_WARNING);

            return $this->nullInstance;
        }

        $instance = $this->instances[$type];

        if ($instance instanceof TypeInterface) {
            return $instance;
        }

        if (is_string($instance) && class_exists($this->instances[$type])) {
            return $this->instances[$type] = new $instance();
        }

        trigger_error(sprintf("class '%s' does not exists", (string)$instance), E_USER_WARNING);
        unset($this->instances[$type]);

        return $this->nullInstance;
    }
}
