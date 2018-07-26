<?php

namespace MakinaCorpus\Ucms\Dashboard\Twig;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Displays any object's actions
 */
class ActionExtension extends \Twig_Extension
{
    private $actionRegistry;
    private $requestStack;
    private $urlGenerator;
    private $skin;

    /**
     * Default constructor
     *
     * @param ActionRegistry $actionRegistry
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(ActionRegistry $actionRegistry, RequestStack $requestStack, UrlGeneratorInterface $urlGenerator)
    {
        $this->actionRegistry = $actionRegistry;
        $this->requestStack = $requestStack;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Get current widget skin
     */
    public function getSkin(): string
    {
        return $this->skin ?? 'seven';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('ucms_actions', [$this, 'renderActions'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('ucms_actions_raw', [$this, 'renderActionsRaw'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('ucms_actions_url', [$this, 'renderActionUrl'], ['is_safe' => ['html']]),
            new \Twig_SimpleFunction('ucms_button', [$this, 'renderSingleAction'], ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('ucms_primary', [$this, 'renderPrimaryActions'], ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    /**
     * Render a singe action
     *
     * @param \Twig_Environment $environment
     *   Twig environment
     * @param array $options
     *   Options that will be given to Action::create()
     * @param string $showTitle
     *   Should title should be displayed
     *
     * @return string
     */
    public function renderSingleAction(\Twig_Environment $environment, array $options, $showTitle = false)
    {
        return $environment->render(sprintf('@ucms_dashboard/action/actions-%s.html.twig', $this->getSkin()), [
            'show_title'  => $showTitle,
            'action'      => Action::create($options),
        ]);
    }

    /**
     * Render actions
     *
     * @param \Twig_Environment $environment
     *   Twig environment
     * @param mixed $item
     *   Item for which to display actions
     * @param string $icon
     *   Icon identifier for primary button
     * @param string $mode
     *   Can be 'link' or 'icon', determine only the primary icon style
     * @param string $title
     *   Title to display in place of primary actions
     * @param string $showTitle
     *   Should title should be displayed
     *
     * @return mixed
     *   Rendered actions
     */
    public function renderActions(\Twig_Environment $environment, $item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        return $this->renderActionsRaw($environment, $this->actionRegistry->getActions($item, false), $icon, $mode, $title, $showTitle);
    }

    /**
     * Render primary actions only
     *
     * @param \Twig_Environment $environment
     *   Twig environment
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
    public function renderPrimaryActions(\Twig_Environment $environment, $item, $icon = null, $mode = 'icon', $title = null, $showTitle = false)
    {
        return $this->renderActionsRaw($environment, $this->actionRegistry->getActions($item, true), $icon, $mode, $title, $showTitle);
    }

    /**
     * Render action link
     *
     * @param Action $action
     *
     * @return string
     */
    public function renderActionUrl($action)
    {
        if (!$action instanceof Action) {
            return '';
        }

        $route = $action->getRoute();
        $parameters = $action->getRouteParameters();

        // @todo find a way for html classes
        if ($action->isDialog()) {
            $parameters['minidialog'] = 1;
        } else if ($action->isAjax()) {
            // @todo anything?
        }

        return $this->urlGenerator->generate($route, $parameters);
    }

    /**
     * Render arbitrary actions
     *
     * @param \Twig_Environment $environment
     *   Twig environment
     * @param Action[] $action
     *   Actions to render
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
    public function renderActionsRaw(\Twig_Environment $environment, array $actions, $icon = '', $mode = 'icon', $title = '', $showTitle = false)
    {
        $context = [
            'title'       => $title,
            'icon'        => $icon,
            'show_title'  => $showTitle,
            'mode'        => $mode,
        ];

        /** @var \MakinaCorpus\Ucms\Dashboard\Action\Action $action */
        foreach ($actions as $key => $action) {
            // Remove actions for which the path is the same.
            /* @todo
            if (current_path() === $action->getRoute()) {
                continue;
            }
             */

            if ($action->isPrimary()) {
                $target = 'primary';
            } else {
                $target = 'secondary';
            }

            $context[$target][$action->getGroup()][$key] = $action;
        }

        foreach (['primary', 'secondary'] as $target) {
            if (isset($context[$target])) {
                foreach ($context[$target] as &$group) {
                    usort($group, function (Action $a, Action $b) {
                        return $a->getPriority() - $b->getPriority();
                    });
                }
            } else {
                $context[$target] = [];
            }
        }

        return $environment->render(sprintf('@ucms_dashboard/action/actions-%s.html.twig', $this->getSkin()), $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ucms_action';
    }
}
