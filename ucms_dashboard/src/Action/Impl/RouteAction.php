<?php

namespace MakinaCorpus\Ucms\Dashboard\Action\Impl;

use Drupal\Core\Url;
use MakinaCorpus\Ucms\Dashboard\Action\AbstractAction;

final class RouteAction extends AbstractAction
{
    private $linkOptions = [];
    private $route = '';
    private $routeParameters = [];
    private $target = '';
    private $withAjax = false;
    private $withDestination = false;
    private $withDialog = false;

    /**
     * Create instance from array
     */
    public static function create(string $id, string $route, array $routeParameters = [], array $options): RouteAction
    {
        $instance = new self();
        self::populate($instance, $id, $options);

        $instance->route = $route;
        $instance->target = (string)($routeParameters['target'] ?? '');
        $instance->withDestination = (bool)($options['destination'] ?? false);

        if (\is_array($routeParameters)) {
            $instance->routeParameters = $routeParameters;
        } else {
            switch ($routeParameters) {

              case 'blank':
                  $instance->target = '_blank';
                  break;

              case 'ajax':
                  $instance->withDestination = true;
                  break;

              case 'dialog':
                  $instance->withDialog = true;
                  $instance->withAjax = true;
                  break;
            }
        }

        return $instance;
    }

    /**
     * Explicit new is disallowed from the outside world.
     */
    protected function __construct()
    {
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
}
