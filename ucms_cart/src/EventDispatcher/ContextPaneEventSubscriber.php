<?php

namespace MakinaCorpus\Ucms\Cart\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Controller\PageRenderer;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Add lots of stuff into the context pane.
 */
class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    private $container;
    private $pageRenderer;
    private $typeHandler;

    /**
     * Default constructor
     */
    public function __construct(TypeHandler $typeHandler, ContainerInterface $container, PageRenderer $pageRenderer)
    {
        $this->container = $container;
        $this->pageRenderer = $pageRenderer;
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
     * @param ContextPaneEvent $event
     */
    public function onContextPaneInit(ContextPaneEvent $event)
    {
        $contextPane = $event->getContextPane();
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
            ;
        }
        if (user_access('use context pane content search')) {
            $contextPane
                // All content
                ->addTab('cart_content', $this->t("All content"), 'file')
                ->add(
                    $this->pageRenderer->renderPage(
                        'ucms_cart.content',
                        $request,
                        ['base_query' => ['type' => $this->typeHandler->getContentTypes()]]
                    ),
                    'cart_content'
                )
                // All media
                ->addTab('cart_media', $this->t("All medias"), 'picture-o')
                ->add(
                    $this->pageRenderer->renderPage(
                        'ucms_cart.content',
                        $request,
                        ['base_query' => ['type' => $this->typeHandler->getMediaTypes()]]
                    ),
                    'cart_media'
                )
            ;
        }
    }
}
