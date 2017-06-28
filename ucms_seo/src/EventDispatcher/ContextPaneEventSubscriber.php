<?php

namespace MakinaCorpus\Ucms\Seo\EventDispatcher;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Action\Action;
use MakinaCorpus\Drupal\Calista\EventDispatcher\ContextPaneEvent;
use MakinaCorpus\Ucms\Seo\SeoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ContextPaneEventSubscriber implements EventSubscriberInterface
{
    use StringTranslationTrait;

    /**
     * @var \MakinaCorpus\Ucms\Seo\SeoService
     */
    private $seoService;

    /**
     * Default constructor
     */
    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
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

        // Add link on redirect list
        // FIXME kill it with a spoon! https://www.youtube.com/watch?v=9VDvgL58h_Y
        if ($router_item['path'] == 'node/%/seo-redirects') {
            $node = $router_item['map'][1];
            if ($this->seoService->userCanEditNodeSeo(\Drupal::currentUser(), $node)) {
                $actions = [
                    new Action($this->t("Add SEO redirect"), 'node/'.$node->nid.'/seo-add-redirect', 'dialog', 'random', 1, true, true, false, 'seo'),
                ];
                $contextPane->addActions($actions, $this->t("Add SEO redirect"), 'random', true);
            }
        }
    }
}
