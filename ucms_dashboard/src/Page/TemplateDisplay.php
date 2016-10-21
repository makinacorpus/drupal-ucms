<?php

namespace MakinaCorpus\Ucms\Dashboard\Page;

/**
 * Default implementation that will always fallback to a template
 *
 * This implementation only needs a template identifier that Twig can link
 * to real a file using the template locator.
 *
 * @deprecated
 *   Please use the PageBuilder object and service instead
 */
class TemplateDisplay extends AbstractDisplay
{
    private $twig;
    private $name;

    /**
     * Default constructor
     *
     * @param \Twig_Environment $twig
     * @param string $templateName
     */
    public function __construct(\Twig_Environment $twig, $templateName)
    {
        $this->twig = $twig;
        $this->name = $templateName;
    }

    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $items)
    {
        trigger_error("Please use the PageBuilder instead.", E_USER_DEPRECATED);

        return $this->twig->render($this->name, [
            'items' => $items,
            'mode'  => $mode,
        ]);
    }
}
