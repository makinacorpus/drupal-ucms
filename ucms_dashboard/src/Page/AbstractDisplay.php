<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;

abstract class AbstractDisplay implements DisplayInterface
{
    use StringTranslationTrait;
    
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
     * @var ActionRegistry
     */
    private $actionRegistry;

    /**
     * Set action registry
     *
     * @param ActionRegistry $actionRegistry
     */
    public function setActionRegistry(ActionRegistry $actionRegistry)
    {
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * Set default mode
     *
     * @param string $mode
     *
     * @return AbstractDisplay
     */
    final public function setDefaultMode($mode)
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
    final public function getDefaultMode()
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
     * @return AbstractDisplay
     */
    final public function setParameterName($parameterName)
    {
        $this->parameterName = $parameterName;

        return $this;
    }

    /**
     * Prepare instance from query
     *
     * @param string[] $query
     *
     * @return AbstractDisplay
     */
    final public function prepareFromQuery($query)
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
     * Get actions for item
     *
     * @param mixed $item
     *
     * @return Action[]
     */
    protected function getActions($item)
    {
        if (!$this->actionRegistry) {
            return [];
        }

        return $this->actionRegistry->getActions($item);
    }

    /**
     * Render view links
     *
     * @param string $route
     *
     * @return array
     *   drupal_render() friendly structure
     */
    final public function renderLinks($route)
    {
        $links = [];

        $supportedMode = $this->getSupportedModes();
        if (count($supportedMode) < 2) {
            return [];
        }

        foreach ($supportedMode as $name => $title) {
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
                'href'        => $route,
                'title'       => $this->t("Display as @mode", ['@mode' => $title]),
                'query'       => $query,
                'attributes'  => $attributes,
                // Forces the l() function to skip the 'active' class by adding empty
                // attributes array and settings a stupid language onto the link (this
                // is Drupal 7 specific and exploit a Drupal weird behavior)
                'language'    => (object)['language' => 'und'],
            ];
        }

        return [
            '#theme' => 'links__ucms_contrib_display_switch',
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
    protected function getSupportedModes()
    {
        return ['default' => 'default'];
    }

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
    final public function render($items)
    {
        return $this->displayAs($this->currentMode, $items);
    }
}
