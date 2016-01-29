<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteFinder;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Request site creation form
 */
class SiteSwitch extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site_finder'),
            $container->get('event_dispatcher')
        );
    }

    /**
     * @var SiteFinder
     */
    private $siteFinder;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * Default constructor
     *
     * @param SiteFinder $siteFinder
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(SiteFinder $siteFinder, EventDispatcherInterface $dispatcher)
    {
        $this->siteFinder = $siteFinder;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_switch';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null, $state = null)
    {
        $form_state->setTemporaryValue('site', $site);
        $form_state->setTemporaryValue('state', $state);

        return confirm_form($form, $this->t("Switch site @site to state @state ?", [
            '@site'   => $site->title,
            '@state'  => SiteState::getList()[$state],
        ]), 'admin/dashboard/site/' . $site->id);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site = $form_state->getTemporaryValue('site');
        $state = (int)$form_state->getTemporaryValue('state');
        $data = ['from' => $site->state, 'to' => $state];
        $list = SiteState::getList();

        $site->state = $state;
        $this->siteFinder->save($site, ['state']);
        drupal_set_message($this->t("Site @site has been switched from @from to @to", [
            '@site' => $site->title,
            '@from' => $list[$data['from']],
            '@to'   => $list[$data['to']],
        ]));

        $this->dispatcher->dispatch('site:switch', new ResourceEvent('site', $site->id, $this->currentUser()->uid, $data));

        $form_state->setRedirect('admin/dashboard/site');
    }
}
