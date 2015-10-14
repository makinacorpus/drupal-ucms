<?php

namespace Ucms\Contrib;

abstract class AbstractListDisplay
{
    /**
     * @var string
     */
    private $parameterName = 'disp';

    /**
     * @var string
     */
    private $currentMode;

    /**
     * @var string
     */
    private $defaultMode;

    /**
     * Get this list arbitrary type identifier, used to hint theme hooks
     *
     * @return string
     */
    public function getType()
    {
        return 'default';
    }

    /**
     * Set default mode
     *
     * @param string $mode
     *
     * @return \Ucms\Contrib\AbstractListDisplay
     */
    public function setDefaultMode($mode)
    {
        if (!isset($this->getSupportedModes()[$mode])) {
            throw new \InvalidArgumentException(sprintf("'%s' does not support input mode '%s'", self::class, $mode));
        }

        $this->defaultMode = $mode;

        return $this;
    }

    /**
     * Get default mode
     *
     * If none set get the first one per default
     *
     * @return string
     */
    public function getDefaultMode()
    {
        if ($this->defaultMode) {
            return $this->defaultMode;
        }

        $supported = $this->getSupportedModes();

        return key($supported);
    }

    /**
     * Set query parameter name
     *
     * @param string $parameterName
     *
     * @return \Ucms\Contrib\AbstractListDisplay
     */
    public function setParameterName($parameterName)
    {
        $this->parameterName = $parameterName;

        return $this;
    }

    /**
     * Prepare instance from query
     *
     * @param string[] $query
     *
     * @return \Ucms\Contrib\AbstractListDisplay
     */
    public function prepareFromQuery($query)
    {
        $mode = null;

        if (isset($query[$this->parameterName])) {
            $mode = $query[$this->parameterName];

            if (!isset($this->getSupportedModes()[$mode])) {
                trigger_error(sprintf("'%s' does not support input mode '%s', fallback to default", self::class, $mode), E_USER_WARNING);
            }
        }

        $default = $this->getDefaultMode();

        if (!$mode) {
            if (!$default) {
                throw new \LogicException(sprintf("'%s' has no default display mode", self::class));
            }

            $mode = $default;
        }

        $this->currentMode = $mode;

        return $this;
    }

    /**
     * Render view links
     *
     * @param string $targetPath
     *   Path to use if not the actual one
     *
     * @return array
     *   drupal_render() friendly structure
     */
    public function renderLinks($targetPath = null)
    {
        $links = [];

        if (!$targetPath) {
            $targetPath = current_path();
        }

        foreach ($this->getSupportedModes() as $name => $title) {
            $attributes = [];

            if ($name === $this->getDefaultMode()) {
                $query = drupal_get_query_parameters(null, ['q', $this->parameterName]);
            } else {
                $query = [$this->parameterName => $name] + drupal_get_query_parameters();
            }

            if ($name === $this->currentMode) {
                $attributes['class'][] = 'active';
            }

            $links[$name] = [
                'href'        => $targetPath,
                'title'       => t("Display as @mode", ['@mode' => $title]),
                'query'       => $query,
                'attributes'  => $attributes,
            ];
        }

        return [
            '#theme' => 'links__ucms_contrib_dislay_switch',
            '#links' => $links,
        ];
    }

    /**
     * Get a list of supported modes
     *
     * Supported modes
     *
     * @param string[]
     *   Keys are internal names, values are human readable names
     */
    abstract protected function getSupportedModes();

    /**
     * Render content
     *
     * @param string $mode
     *   Current display mode (eg. 'list', 'table', ...) 
     * @param mixed[] $items
     *
     * @return array
     *   drupal_render() friendly structure
     */
    abstract protected function displayAs($mode, $items);

    /**
     * Render content (object must be prepared with query)
     *
     * @param mixed[] $items
     *
     * @return array
     *   drupal_render() friendly structure
     */
    public function render($items)
    {
        return $this->displayAs($this->currentMode, $items);
    }
}
