<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Dashboard\Action\Action;
use MakinaCorpus\Drupal\Dashboard\Action\ActionProviderInterface;
use MakinaCorpus\Drupal\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Drupal\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Drupal\Dashboard\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Contrib\Controller\CartController;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;
    use PageControllerTrait;

    private $container;
    private $contentActionProvider;
    private $siteManager;
    private $typeHandler;
    private $actionProviderRegistry;

    /**
     * Default constructor
     *
     * @param ActionProviderInterface $contentActionProvider
     * @param ActionProviderInterface $actionRegistry
     * @param SiteManager $siteManager
     * @param TypeHandler $typeHandler
     */
    public function __construct(
        ContainerInterface $container,
        ActionProviderInterface $contentActionProvider,
        ActionRegistry $actionRegistry,
        SiteManager $siteManager,
        TypeHandler $typeHandler
    ) {
        $this->container = $container;
        $this->contentActionProvider = $contentActionProvider;
        $this->actionProviderRegistry = $actionRegistry;
        $this->siteManager = $siteManager;
        $this->typeHandler = $typeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ContextPaneEvent::EVENT_INIT => [
                ['onUcmsdashboardContextinit', 0],
            ],
        ];
    }

    /**
     * Get service in container
     *
     * @param string $id
     *
     * @return object
     */
    final protected function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * Render one's favorite cart.
     * @todo Better way
     */
    private function renderCart()
    {
        // @todo keeping controller for now because it does handles the
        //   javascript includes for Drupal, and we need it
        $controller = new CartController();
        $controller->setContainer($this->container);

        return $controller->renderAction($this->container->get('request_stack')->getCurrentRequest());
    }

    /**
     * Render one's favorite cart.
     * @todo Better way
     */
    private function renderBrowseHistory()
    {
        $request = $this->get('request_stack')->getCurrentRequest();
        $builder = $this->getPageBuilder('cart_all', $request);

        // @todo we must find a more straight-foward way
        return $builder->searchAndRender($request);
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
                ->add($this->renderCart(), 'cart')
                ->addTab('cart_all', $this->t("All content"), 'search')
                ->add($this->renderBrowseHistory(), 'cart_all')
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
        if (substr(current_path(), 0, 16) == 'admin/dashboard/' && in_array(arg(2), ['content', 'media'])) {
            if (arg(2) == 'content') {
                $actions = [];
                $actions = array_merge($actions, $this->contentActionProvider->getActions('editorial'));
                $actions = array_merge($actions, $this->contentActionProvider->getActions('component'));
                $contextPane->addActions($actions, $this->t("Create"), 'plus');
            } else {
                $actions = [];
                $actions = array_merge($actions, $this->contentActionProvider->getActions('media'));
                $contextPane->addActions($actions, $this->t("Create"), 'plus');
            }
        }

        // Add node creation link on site
        // FIXME kill it with acid!
        if ($this->siteManager->hasContext() && !path_is_admin(current_path())) {
            $actions = [];
            $actions = array_merge($actions, $this->contentActionProvider->getActions('editorial'));
            $actions = array_merge($actions, $this->contentActionProvider->getActions('media'));
            $actions = array_merge($actions, $this->contentActionProvider->getActions('component'));
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
