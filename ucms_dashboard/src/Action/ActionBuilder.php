<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

final class ActionBuilder
{
    private $id;
    private $options = [];

    /**
     * Default constructor
     */
    public function __construct(string $id, string $title, string $icon = null, int $priority = 0, $group = Action::GROUP_DEFAULT, bool $primary = false)
    {
        $this-> id = $id;
        $this->options = [
            'title' => $title,
            'icon' => $icon,
            'group' => $group,
            'priority' => $priority,
            'primary' => $primary,
        ];
    }

    /**
     * Set grant condition
     *
     * @param bool|callable $predicate
     */
    public function isGranted($predicate): self
    {
        $this->options['is_granted'] = $predicate;

        return $this;
    }

    /**
     * Set the action in disabled state
     */
    public function disable(): self
    {
        $this->options['disable'] = true;

        return $this;
    }

    /**
     * Set priority
     */
    public function priority(int $value): self
    {
        $this->options['priority'] = $value;

        return $this;
    }

    /**
     * Set group
     */
    public function group(string $value): self
    {
        $this->options['group'] = $value;

        return $this;
    }

    /**
     * Set as primary
     */
    public function primary(): self
    {
        $this->options['group'] = true;

        return $this;
    }

    /**
     * Build the action as a link
     */
    public function asLink(string $route, array $routeParameters = []): RouteAction
    {
        return RouteAction::create($this->id, $route, $routeParameters, $this->options);
    }

    /**
     * Build the action as something that can be processed
     */
    public function asAction(callable $process)
    {
        return ProcessAction::create($this->id, $process, $this->options);
    }
}
