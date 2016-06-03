<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
class Action
{
    static public function create($options)
    {
        $options += [
            'title'     => null,
            'uri'       => null,
            'options'   => [],
            'icon'      => null,
            'priority'  => 0,
            'primary'   => true,
            'redirect'  => false,
            'disabled'  => false,
            'group'     => null,
        ];

        return new static(
            $options['title'],
            $options['uri'],
            $options['options'],
            $options['icon'],
            $options['priority'],
            $options['primary'],
            $options['redirect'],
            $options['disabled'],
            $options['group']
        );
    }

    private $title;

    private $uri;

    private $linkOptions = [];

    private $priority;

    private $icon;

    private $primary = true;

    private $disabled = false;

    private $group = null;

    /**
     * Default constructor
     *
     * @param string $title
     *   Human readable action
     * @param string $uri
     *   Resource URI, if no scheme will be used as a Drupal path
     * @param string|array $options
     *   Link options, see the l() and url() functions altogether
     *   It can be one of those values:
     *     'dialog' : load the page in a dialog
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
    public function __construct($title, $uri = null, $options = [], $icon = null, $priority = 0, $primary = true, $addCurrentDestination = false, $disabled = false, $group = null)
    {
        $this->title = $title;
        $this->uri = $uri;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->primary = $primary;
        $this->disabled = $disabled;
        $this->group = $group;

        if (is_array($options)) {
            $this->linkOptions = $options;
        } else {
            switch ($options) {

              case 'ajax':
                  $this->linkOptions = [
                      'attributes' => ['class' => ['use-ajax']],
                      'query' => drupal_get_destination(),
                  ];
                  break;

              case 'dialog':
                  $this->linkOptions = [
                      'attributes' => ['class' => ['use-ajax', 'minidialog']],
                      'query' => ['minidialog' => 1],
                  ];
                  if ($addCurrentDestination) {
                      $this->linkOptions['query'] += drupal_get_destination();
                  }
                  break;
            }
        }

        $this->linkOptions['attributes']['title'] = $this->title;

        if ($disabled) {
            $this->linkOptions['attributes']['class'][] = 'disabled';
        }

        if ($addCurrentDestination && !isset($this->linkOptions['query']['destination'])) {
            if (!isset($this->linkOptions['query'])) {
                $this->linkOptions['query'] = [];
            }
            $this->linkOptions['query'] += drupal_get_destination();
        }
    }

    public function getGroup()
    {
        return $this->group;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getURI()
    {
        return $this->uri;
    }

    public function getLinkOptions()
    {
        return $this->linkOptions;
    }

    public function getIcon()
    {
        return $this->icon;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function isPrimary()
    {
        return $this->primary;
    }

    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param boolean $primary
     */
    public function setPrimary($primary)
    {
        $this->primary = $primary;
    }
}
