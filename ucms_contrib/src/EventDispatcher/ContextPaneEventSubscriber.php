<?php

namespace MakinaCorpus\Ucms\Contrib\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Action\ActionProviderInterface;
use MakinaCorpus\Calista\Action\ActionRegistry;
use MakinaCorpus\Calista\Controller\PageRenderer;
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

    private $actionProviderRegistry;
    private $container;
    private $contentActionProvider;
    private $pageRenderer;
    private $siteManager;
    private $typeHandler;

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
        TypeHandler $typeHandler,
        PageRenderer $pageRenderer
    ) {
        $this->container = $container;
        $this->contentActionProvider = $contentActionProvider;
        $this->actionProviderRegistry = $actionRegistry;
        $this->siteManager = $siteManager;
        $this->typeHandler = $typeHandler;
        $this->pageRenderer = $pageRenderer;
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
     * @param ContextPaneEvent $event
     */
    public function onContextPaneInit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();
        $router_item = menu_get_item();
        $request = $this->container->get('request_stack')->getCurrentRequest();

        // Add the shopping cart
        if (user_access('use favorites')) {
            $contextPane
                // User cart
                ->addTab('cart', $this->t("Cart"), 'shopping-cart')
                ->add(
                    $this->pageRenderer->renderPage(
                        'ucms_cart',
                        $request,
                        ['base_query' => ['cart_user_id' => $GLOBALS['user']->uid /* @fixme */]]
                    ),
                    'cart'
                )
                // All content
                ->addTab('cart_content', $this->t("All content"), 'file')
                ->add(
                    $this->pageRenderer->renderPage(
                        'ucms_cart',
                        $request,
                        ['base_query' => ['type' => $this->typeHandler->getContentTypes()]]
                    ),
                    'cart_content'
                )
                // All media
                ->addTab('cart_media', $this->t("All medias"), 'picture-o')
                ->add(
                    $this->pageRenderer->renderPage(
                        'ucms_cart',
                        $request,
                        ['base_query' => ['type' => $this->typeHandler->getMediaTypes()]]
                    ),
                    'cart_media'
                )
            ;
        }

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
