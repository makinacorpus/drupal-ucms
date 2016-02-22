<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\Context as LayoutContext;

/**
 * Class ContextPaneEventListener
 * @package MakinaCorpus\Ucms\Contrib\EventDispatcher
 */
class ContextPaneEventListener
{
    use StringTranslationTrait;

    /**
     * @var LayoutContext
     */
    private $layoutContext;

    /**
     * @var ActionProviderInterface
     */
    private $actionProvider;

    /**
     * Default constructor
     *
     * @param LayoutContext $layoutContext
     * @param ActionProviderInterface $actionProvider
     */
    public function __construct(LayoutContext $layoutContext, ActionProviderInterface $actionProvider)
    {
        $this->layoutContext = $layoutContext;
        $this->actionProvider = $actionProvider;
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();

        // Add the shopping cart
        if (user_access('use favorites')) {
            // On admin lists, on content creation or on layout edit
            $router_item = menu_get_item();
            $allowed_routes = [
                'node/%',
                'admin/dashboard/content',
                'admin/dashboard/media',
            ];
            // @todo Inject services
            if (in_array($router_item['path'], $allowed_routes)
                || in_array($router_item['tab_parent'], $allowed_routes)
                || $this->layoutContext->isTemporary()
            ) {
                $contextPane
                    ->addTab('cart', t("Cart"), 'shopping-cart')
                    ->add(ucms_contrib_favorite_render(), 'cart')
                ;
            }
        }

        // Add a backlink
        //   @todo find a solution for path_is_admin() and current_path()
        //     maybe bring in the RequestStack
        if (!path_is_admin(current_path())) {
            $backlink = new Action(t("Go to dashboard"), 'admin/dashboard', null, 'dashboard');
            $contextPane->addActions([$backlink]);
        }
        /*else {
            // @Todo possibly store the last site visited in the session to provide a backlink
            $backlink = new Action(t("Go to site"), '<front>', null, 'globe');
        }*/

        // Add node creation link
        if (substr(current_path(), 0, 16) == 'admin/dashboard/' && in_array(arg(2), ['content', 'media'])) {
            $contextPane->addActions($this->actionProvider->getActions(arg(2)), $this->t("Create item"));
        }
    }
}
