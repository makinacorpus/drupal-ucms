<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * Default implementation, just throw everything you've got in there
 */
class FilterDisplay
{
    /**
     * @var mixed
     */
    private $render;

    /**
     * @var string
     */
    private $title;

    /**
     * Default constructor
     *
     * @param string $title
     *   Title
     * @param mixed $render
     *   drupal_render() friendly structure
     */
    public function __construct($title, $render = null)
    {
        $this->title = $title;
        $this->render = $render;
    }

    /**
     * {inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {inheritdoc}
     */
    public function render()
    {
        $output = drupal_render($this->render);

        if (!empty($output)) {
            return $output;
        }
    }
}
