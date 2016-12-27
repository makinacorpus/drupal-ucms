<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

class PageView
{
    private $twig;
    private $template;
    private $arguments;

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string $template
     * @param mixed[] $arguments
     */
    public function __construct(\Twig_Environment $twig, $template, array $arguments = [])
    {
        $this->twig = $twig;
        $this->template = $template;
        $this->arguments = $arguments;
    }

    /**
     * Render a single block of this page
     *
     * @param string $block
     *
     * @return string
     */
    public function renderPartial($block)
    {
        return $this->twig->load($this->template)->renderBlock($block, $this->arguments);
    }

    /**
     * Render the page
     *
     * @return string
     */
    public function render()
    {
        return $this->renderPartial('page');
    }

    /**
     * Alias of ::render()
     *
     * @return string
     */
    public function __toString()
    {
        trigger_error("__toString() usage is dangerous, please render the page explicitely", E_USER_DEPRECATED);

        return $this->renderPartial('page');
    }
}
