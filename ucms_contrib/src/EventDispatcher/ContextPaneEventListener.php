<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Layout\ContextManager as LayoutContextManager;
use MakinaCorpus\Ucms\Site\SiteManager;

/**
 * Class ContextPaneEventListener
 * @package MakinaCorpus\Ucms\Contrib\EventDispatcher
 */
class ContextPaneEventListener
{
    use StringTranslationTrait;

    /**
     * @var LayoutContextManager
     */
    private $layoutContextManager;

    /**
     * @var ActionRegistry
     */
    private $contentActionProvider;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var \MakinaCorpus\Ucms\Contrib\TypeHandler
     */
    private $typeHandler;
    /**
     * @var ActionProviderInterface
     */
    private $actionProviderRegistry;

    /**
     * Default constructor
     *
     * @param LayoutContextManager $layoutContextManager
     * @param ActionProviderInterface $contentActionProvider
     * @param ActionProviderInterface $actionProviderRegistry
     * @param SiteManager $siteManager
     * @param TypeHandler $typeHandler
     */
    public function __construct(
        LayoutContextManager $layoutContextManager,
        ActionProviderInterface $contentActionProvider,
        ActionRegistry $actionRegistry,
        SiteManager $siteManager,
        TypeHandler $typeHandler
    ) {
        $this->layoutContextManager = $layoutContextManager;
        $this->contentActionProvider = $contentActionProvider;
        $this->actionProviderRegistry = $actionRegistry;
        $this->siteManager = $siteManager;
        $this->typeHandler = $typeHandler;
    }

    /**
     * @param ContextPaneEvent $event
     */
    public function onUcmsdashboardContextinit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();
        $router_item = menu_get_item();

        // Add the shopping cart
        if (user_access('use favorites')) {
            // On admin lists, on content creation or on layout edit
            $allowed_routes = [
                'node/%',
                'admin/dashboard/content',
                'admin/dashboard/media',
                'admin/dashboard/tree',
            ];
            // @todo Inject services
            if (
                in_array($router_item['path'], $allowed_routes) ||
                in_array($router_item['tab_parent'], $allowed_routes) ||
                $this->layoutContextManager->isInEditMode()
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
            $contextPane->addActions([$backlink], null, 'dashboard', false);
        }
        /*else {
            // @Todo possibly store the last site visited in the session to provide a backlink
            $backlink = new Action($this->t("Go to site"), '<front>', null, 'globe');
        }*/

        // Add node creation link on dashboard
        // FIXME kill it with fire!
        if (substr(current_path(), 0, 16) == 'admin/dashboard/' && in_array(arg(2), ['content', 'media'])) {
            if (arg(2) == 'content') {
                $contextPane->addActions($this->contentActionProvider->getActions('editorial'), $this->t("Create editorial content"), 'file');
                $contextPane->addActions($this->contentActionProvider->getActions('component'), $this->t("Create component"), 'tasks');
            }
            else {
                $contextPane->addActions($this->contentActionProvider->getActions('media'), $this->t("Create media"), 'picture');
            }
        }

        // Add node creation link on site
        // FIXME kill it with acid!
        if ($this->siteManager->hasContext()) {
            $contextPane->addActions($this->contentActionProvider->getActions('editorial'), $this->t("Create editorial content"), 'file');
            $contextPane->addActions($this->contentActionProvider->getActions('component'), $this->t("Create component"), 'tasks');
            $contextPane->addActions($this->contentActionProvider->getActions('media'), $this->t("Create media"), 'picture');
        }

        // Add node link on node view
        // FIXME kill it with lasers!
        if ($router_item['path'] == 'node/%') {
            $node = $router_item['map'][1];
            $actions = $this->actionProviderRegistry->getActions($node);
            foreach ($actions as $action) { // this... this is sorcery...
                if ('pencil' === $action->getIcon()) {
                    $action->setPrimary(true);
                } else if ($action->isPrimary()) {
                    $action->setPrimary(false);
                }
            }
            $contextPane->addActions($actions, null, 'pencil', false);
        }
    }
}
