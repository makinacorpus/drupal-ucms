<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\Context as LayoutContext;
use MakinaCorpus\Ucms\Site\SiteManager;

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
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var \MakinaCorpus\Ucms\Contrib\TypeHandler
     */
    private $typeHandler;

    /**
     * Default constructor
     *
     * @param LayoutContext $layoutContext
     * @param ActionProviderInterface $actionProvider
     * @param SiteManager $siteManager
     * @param TypeHandler $typeHandler
     */
    public function __construct(
        LayoutContext $layoutContext,
        ActionProviderInterface $actionProvider,
        SiteManager $siteManager,
        TypeHandler $typeHandler
    )
    {
        $this->layoutContext = $layoutContext;
        $this->actionProvider = $actionProvider;
        $this->siteManager = $siteManager;
        $this->typeHandler = $typeHandler;
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
                    ->addTab('cart', $this->t("Cart"), 'shopping-cart')
                    ->add(ucms_contrib_favorite_render(), 'cart')
                ;
            }
        }

        // Add a backlink
        //   @todo find a solution for path_is_admin() and current_path()
        //     maybe bring in the RequestStack
        if (!path_is_admin(current_path())) {
            $backlink = new Action($this->t("Go to dashboard"), 'admin/dashboard', null, 'dashboard');
            $contextPane->addActions([$backlink]);
        }
        /*else {
            // @Todo possibly store the last site visited in the session to provide a backlink
            $backlink = new Action($this->t("Go to site"), '<front>', null, 'globe');
        }*/

        // Add node creation link on dashboard
        // FIXME kill it with fire!
        if (substr(current_path(), 0, 16) == 'admin/dashboard/' && in_array(arg(2), ['content', 'media'])) {
            if (arg(2) == 'content') {
                $contextPane->addActions($this->actionProvider->getActions('editorial'), $this->t("Create editorial content"));
                $contextPane->addActions($this->actionProvider->getActions('component'), $this->t("Create component"));
            }
            else {
                $contextPane->addActions($this->actionProvider->getActions('media'), $this->t("Create media"));
            }
        }

        // Add node creation link on site
        // FIXME kill it with acid!
        if ($this->siteManager->hasContext()) {
            $contextPane->addActions($this->actionProvider->getActions('editorial'), $this->t("Create editorial content"));
            $contextPane->addActions($this->actionProvider->getActions('component'), $this->t("Create component"));
            $contextPane->addActions($this->actionProvider->getActions('media'), $this->t("Create media"));
        }
    }
}
