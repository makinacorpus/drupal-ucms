<?php

namespace MakinaCorpus\Ucms\Dashboard\Twig\Extension;

use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;

/**
 * Displays any object's actions
 */
class ActionExtension extends \Twig_Extension
{
    private $actionRegistry;

    /**
     * Default constructor
     *
     * @param ActionRegistry $actionRegistry
     */
    public function __construct(ActionRegistry $actionRegistry)
    {
        $this->actionRegistry = $actionRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('ucms_actions', [$this, 'renderActions'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * Render actions
     *
     * @param mixed $item
     *   Item for which to display actions
     * @param string $icon
     *   Icon identifier for primary button
     * @param string $mode
     *   Can be 'link' or 'icon', determine only the primary icon style
     * @param string $title
     *   Title to display in place of primary actions
     * @param string $showTitle
     *   Should title should be displaye
     *
     * @return mixed
     *   Rendered actions
     */
    public function renderActions($item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        // @todo still based upon Drupal, needs fixing
        $output = [
            '#theme'      => 'ucms_dashboard_actions',
            '#actions'    => $this->actionRegistry->getActions($item),
            '#icon'       => $icon,
            '#mode'       => $mode,
            '#title'      => $title,
            '#show_title' => $showTitle,
        ];

        return drupal_render($output);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ucms_dashboard_action';
    }
}
