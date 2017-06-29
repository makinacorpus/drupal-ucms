<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Calista\Action\ActionProviderInterface;
use MakinaCorpus\Calista\Action\ActionRegistry;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add lots of stuff into the context pane.
 */
class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

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
                ['onContextPaneInit', 0],
            ],
        ];
    }

    /**
     * Render page
     *
     * @param string $name
     * @param array $inputOptions
     *
     * @return string
     */
    private function renderPage($name, array $inputOptions = [])
    {
        /** @var \MakinaCorpus\Calista\DependencyInjection\ViewFactory $factory */
        $factory = $this->container->get('calista.view_factory');

        $page = $factory->getPageDefinition($name);
        $viewDefinition = $page->getViewDefinition();
        $view = $factory->getView($viewDefinition->getViewType());

        $request = $this->container->get('request_stack')->getCurrentRequest();

        $query = $page->getInputDefinition($inputOptions)->createQueryFromRequest($request);
        $items = $page->getDatasource()->getItems($query);

        // View must inherit from the page definition identifier to ensure
        // that AJAX queries will work
        $view->setId($page->getId());

        return $view->render($viewDefinition, $items, $query);
    }

    /**
     * Render one's favorite cart.
     * @todo Better way
     *
    private function renderCart()
    {
        // @todo keeping controller for now because it does handles the
        //   javascript includes for Drupal, and we need it
        $controller = new CartController();
        $controller->setContainer($this->container);

        return $controller->renderAction($this->container->get('request_stack')->getCurrentRequest());
    }
        if ($this->tab) {
            switch ($this->tab) {

                case 'content':
                    $baseQuery['type'] = $this->typeHandler->getContentTypes();
                    $searchParam = 'ccs';
                    break;

                case 'media':
                    $baseQuery['type'] = $this->typeHandler->getMediaTypes();
                    $searchParam = 'cms';
                    break;
            }
        }

     */

    /**
     * @param ContextPaneEvent $event
     */
    public function onContextPaneInit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();
        $router_item = menu_get_item();

        // Add the shopping cart
        if (user_access('use favorites')) {
            $contextPane
                ->addTab('cart', $this->t("Cart"), 'shopping-cart')
                ->add($this->renderPage('ucms_cart'), 'cart')
//                 ->addTab('cart_content', $this->t("All content"), 'file')
//                 ->add($this->renderPage('cart_content'), 'cart_content')
//                 ->addTab('cart_media', $this->t("All medias"), 'picture')
//                 ->add($this->renderPage('cart_media'), 'cart_media')
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
