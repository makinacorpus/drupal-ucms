<?php

namespace MakinaCorpus\Ucms\Dashboard\Action;

/**
 * Represent a possible action over a certain item, this is just a value
 * object that will be used to build UI links or buttons
 */
class Action
{
    private $title;

    private $uri;

    private $linkOptions = [];

    private $priority;

    private $icon;

    private $primary = true;

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
     */
    public function __construct($title, $uri, $options = [], $icon = null, $priority = 0, $primary = true, $addCurrentDestination = false)
    {
        $this->title = $title;
        $this->uri = $uri;
        $this->icon = $icon;
        $this->priority = $priority;
        $this->primary = $primary;

        if (is_array($options)) {
            $this->linkOptions = $options;
        } else {
            switch ($options) {

              case 'dialog':
                  $this->linkOptions = [
                    'attributes' => ['class' => ['use-ajax', 'minidialog']],
                    'query' => ['minidialog' => 1] + drupal_get_destination(),
                  ];
                  break;
            }
        }

        if ($addCurrentDestination && !isset($this->linkOptions['query']['destination'])) {
            if (!isset($this->linkOptions['query'])) {
                $this->linkOptions['query'] = [];
            }
            $this->linkOptions['query'] += drupal_get_destination();
        }
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
}
