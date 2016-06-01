<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Reference a node on a site
 */
class NodeDereferenceFrom extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('ucms_site.node_manager'),
            $container->get('event_dispatcher')
        );
    }

    protected $siteManager;
    protected $nodeManager;
    protected $eventDispatcher;

    public function __construct(SiteManager $siteManager, NodeManager $nodeManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->siteManager = $siteManager;
        $this->nodeManager = $nodeManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_node_dereference_from';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        if (!$node instanceof NodeInterface) {
            $this->logger('form')->critical("There is no node to dereference!");
            return $form;
        }

        if (!$this->siteManager->hasContext()) {
            $this->logger('form')->critical("There is no site to remove reference from!");
            return $form;
        }

        $form_state->setTemporaryValue('node', $node);
        $site = $this->siteManager->getContext();

        return confirm_form(
            $form,
            $this->t("Remove %title from the %site site?", [
                '%title'  => $node->title,
                '%site'   => $site->title,
            ]),
            'node/' . $node->id()
        );
    }

    /**
     * Step B form submit
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $form_state->getTemporaryValue('node');
        $site = $this->siteManager->getContext();

        $this->nodeManager->deleteReferenceBulkFromSite($site->getId(), [$node->id()]);

        drupal_set_message($this->t("%title has been removed from site %site", [
            '%title'  => $node->title,
            '%site'   => $site->title,
        ]));

        $this->eventDispatcher->dispatch('site:deref', new ResourceEvent('site', $site->getId(), $this->currentUser()->uid, ['nid' => $node->id()]));

        $form_state->setRedirect('node/' . $node->id());
    }
}
