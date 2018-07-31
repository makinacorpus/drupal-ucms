<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

use Drupal\Core\Url;
use Drupal\Core\Language\LanguageInterface;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
class Action
{
    const GROUP_DEFAULT = '';

    /**
     * Create action from array
     *
     * @param mixed[] $options
     *
     * @return Action
     */
    static public function create($options)
    {
        $options += [
            'title'     => '',
            'route'     => '',
            'options'   => [],
            'icon'      => '',
            'priority'  => 0,
            'primary'   => true,
            'redirect'  => false,
            'disabled'  => false,
            'group'     => self::GROUP_DEFAULT,
        ];

        return new static(
            $options['title'],
            $options['route'],
            $options['options'],
            $options['icon'],
            $options['priority'],
            $options['primary'],
            $options['redirect'],
            $options['disabled'],
            $options['group']
        );
    }

    private $disabled = false;
    private $group = null;
    private $icon = '';
    private $linkOptions = [];
    private $primary = true;
    private $priority = 0;
    private $route = '';
    private $routeParameters = [];
    private $target = '';
    private $title = '';
    private $withAjax = false;
    private $withDestination = false;
    private $withDialog = false;

    /**
     * Default constructor
     *
     * @param string $title
     *   Human readable action
     * @param string $route
     *   Symfony route, Drupal path or full URL
     * @param string|array $options
     *   Link options, see the l() and url() functions altogether if you're using Drupal
     *   or it will be used as route parameters for Symfony router
     *   It can be one of those values:
     *     'dialog' : load the page in a dialog
     *     'blank' : load with target=blank
     * @param string $icon
     *   Something that is a bootstrap glyphicon name (easiest way of theming
     *   this, sorry)
     * @param int $priority
     *   Global ordering priority for this action
     * @param boolean $primary
     *   If set to false, this action might be displayed into a second level
     *   actions dropdown instead of being directly accessible
     * @param boolean $addCurrentDestination
     *   If set to true, this code will automatically add the current page as
     *   a query destination for the action
     * @param boolean $disabled
     *   If set to true, action will be disabled
     * @param string $group
     *   An arbitrary string that will be used to group actions altogether
     */
    public function __construct(string $title, string $route = '', $options = [], string $icon = '', int $priority = 0, bool $primary = true, bool $addCurrentDestination = false, bool $disabled = false, string $group = self::GROUP_DEFAULT)
    {
        $this->title = $title;
        $this->route = $route;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->primary = $primary;
        $this->disabled = $disabled;
        $this->group = $group;

        if (is_array($options)) {
            $this->routeParameters = $options;
        } else {
            switch ($options) {

              case 'blank':
                  $this->target = '_blank';
                  break;

              case 'ajax':
                  $addCurrentDestination = true;
                  break;

              case 'dialog':
                  $this->withDialog = true;
                  $this->withAjax = true;
                  break;
            }
        }

        if ($addCurrentDestination) {
            $this->withDestination = true;
            $this->routeParameters['_destination'] = 1;
        }
    }

    /**
     * Get action unique identifier
     */
    public function getDrupalId(): string
    {
        // @todo I am not proud of this one (code from BlockBase).
        $transliterated = \Drupal::transliteration()->transliterate($this->title ?? $this->route, LanguageInterface::LANGCODE_DEFAULT, '_');
        $transliterated = \mb_strtolower($transliterated);
        $transliterated = \preg_replace('@[^a-z0-9_.]+@', '', $transliterated);

        return $transliterated;
    }

    /**
     * Get Drupal URL
     */
    public function getDrupalUrl(): Url
    {
        return new Url($this->route, $this->routeParameters ?? [], $this->linkOptions ?? []);
    }

    /**
     * Does this action needs a destination/redirect parameter
     */
    public function hasDestination(): bool
    {
        return $this->withDestination || $this->withDialog;
    }

    /**
     * Does this action is run via AJAX
     */
    public function isAjax(): bool
    {
        return $this->withAjax;
    }

    /**
     * Does this action is opened in a dialog
     */
    public function isDialog(): bool
    {
        return $this->withDialog;
    }

    /**
     * Has this action a target on link
     */
    public function hasTarget(): bool
    {
        return !empty($this->target);
    }

    /**
     * Get target
     */
    public function getTarget(): string
    {
        return $this->target ?? '';
    }

    /**
     * Get action group
     */
    public function getGroup(): string
    {
        return $this->group ?? self::GROUP_DEFAULT;
    }

    /**
     * Get action title
     */
    public function getTitle(): string
    {
        return $this->title ?? '';
    }

    /**
     * Get action route, can be an already computed URL
     */
    public function getRoute(): string
    {
        return $this->route ?? '';
    }

    /**
     * Get route parameters
     *
     * @return string[]
     *   Route parameters (mostly GET query parameters)
     */
    public function getRouteParameters(): array
    {
        return $this->routeParameters ?? [];
    }

    /**
     * Get link options
     *
     * @return array
     *   For Drupal, this is a suitable array for l() and url() functions, whose
     *   only missing the 'query' key, query must be fetched calling the
     *   getRouteParameters() method.
     */
    public function getOptions(): array
    {
        return $this->linkOptions ?? [];
    }

    /**
     * Get icon
     */
    public function getIcon(): string
    {
        return $this->icon ?? '';
    }

    /**
     * Get action priority (order in list)
     */
    public function getPriority(): int
    {
        return $this->priority ?? 0;
    }

    /**
     * Is the action primary
     */
    public function isPrimary(): bool
    {
        return (bool)$this->primary;
    }

    /**
     * Is the action disabled
     *
     * @return bool
     */
    public function isDisabled(): bool
    {
        return (bool)$this->disabled;
    }

    /**
     * Toggle primary mode
     */
    public function setPrimary(bool $primary)
    {
        $this->primary = $primary;
    }
}
