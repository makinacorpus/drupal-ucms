<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Site\SiteManager;

class ContextPaneEventListener
{
    use StringTranslationTrait;

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
     * @param ActionProviderInterface $contentActionProvider
     * @param ActionProviderInterface $actionProviderRegistry
     * @param SiteManager $siteManager
     * @param TypeHandler $typeHandler
     */
    public function __construct(
        ActionProviderInterface $contentActionProvider,
        ActionRegistry $actionRegistry,
        SiteManager $siteManager,
        TypeHandler $typeHandler
    ) {
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

            $contextPane
                ->addTab('cart', $this->t("Cart"), 'shopping-cart')
                ->add(ucms_contrib_favorite_render(), 'cart')
            ;
        }

        // Add a backlink
        //   @todo find a solution for path_is_admin() and current_path()
        //     maybe bring in the RequestStack
        if (!path_is_admin(current_path()) && user_access('access administration pages')) {
            $backlink = new Action($this->t("Go to dashboard"), 'admin/dashboard', null, 'dashboard');
            $contextPane->addActions([$backlink], null, 'dashboard', false);
        }
        /*else {
            // @Todo possibly store the last site visited in the session to provide a backlink
            $backlink = new Action($this->t("Go to site"), '<front>', null, 'globe');
        }*/

        // Add node creation link on dashboard
        // FIXME kill it with fire!
        if (substr(current_path(), 0, 16) == 'admin/dashboard/' && in_array(arg(2), [TypeHandler::TAB_CONTENT, TypeHandler::TAB_MEDIA])) {
            if (arg(2) == TypeHandler::TAB_CONTENT) {
                $actions = [];
                $actions = array_merge($actions, $this->contentActionProvider->getActions(TypeHandler::TYPE_EDITORIAL));
                $actions = array_merge($actions, $this->contentActionProvider->getActions(TypeHandler::TYPE_COMPONENT));
                $contextPane->addActions($actions, $this->t("Create"), 'plus');
            } else {
                $actions = [];
                $actions = array_merge($actions, $this->contentActionProvider->getActions(TypeHandler::TYPE_MEDIA));
                $contextPane->addActions($actions, $this->t("Create"), 'plus');
            }
        }

        $actions = $this->contentActionProvider->getActions('cart');
        $contextPane->addActions($actions, $this->t("Refresh"), 'refresh');

        // Add node creation link on site
        // FIXME kill it with acid!
        if ($this->siteManager->hasContext() && !path_is_admin(current_path())) {
            $actions = [];
            $actions = array_merge($actions, $this->contentActionProvider->getActions(TypeHandler::TYPE_EDITORIAL));
            $actions = array_merge($actions, $this->contentActionProvider->getActions(TypeHandler::TYPE_MEDIA));
            $actions = array_merge($actions, $this->contentActionProvider->getActions(TypeHandler::TYPE_COMPONENT));
            $contextPane->addActions($actions, $this->t("Create"), 'plus');
        }

        // Add node link on node view
        // FIXME kill it with lasers!
        if ($router_item['path'] == 'node/%' && menu_get_object()) {
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
