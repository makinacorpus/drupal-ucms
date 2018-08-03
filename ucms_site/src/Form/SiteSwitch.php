<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SiteSwitch extends ConfirmFormBase
{
    protected $database;
    protected $dispatcher;
    protected $site;
    protected $siteManager;
    protected $state;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('event_dispatcher'),
            $container->get('database')
        );
    }

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, EventDispatcherInterface $dispatcher, Connection $database)
    {
        $this->database = $database;
        $this->dispatcher = $dispatcher;
        $this->siteManager = $siteManager;
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
        if (!$site) {
            return $form;
        }

        $this->site = $site;
        $this->state = (int)$state;

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        return $this->t("Switch site @site to state @state ?", [
            '@site'  => $this->site->getAdminTitle(),
            '@state' => $this->t(SiteState::getList()[$this->state]),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        return new Url('ucms_site.admin.site_list');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site = $this->site;
        $state = $this->state;
        $data = ['from' => $site->state, 'to' => $state /* , 'message' => $form_state->getValue('message') */];
        $list = SiteState::getList();

        $transaction = null;

        try {
            $transaction = $this->database->startTransaction();

            $site->state = $state;
            $this->siteManager->getStorage()->save($site, ['state']);
            $this->dispatcher->dispatch(SiteEvents::EVENT_SWITCH, new SiteEvent($site, $this->currentUser()->id(), $data));

            // Explicit commit: this is VERY important.
            unset($transaction);

            \drupal_set_message($this->t("Site @site has been switched from @from to @to", [
                '@site' => $site->getAdminTitle(),
                '@from' => $this->t($list[$data['from']]),
                '@to'   => $this->t($list[$data['to']])
            ]));

        } catch (\Exception $e) {
            if ($transaction) {
                try {
                    $transaction->rollback();
                } catch (\Exception $e2) {
                    \watchdog_exception('ucms_site', $e2);
                }
                \watchdog_exception('ucms_site', $e);
                \drupal_set_message($this->t("There was an error switching site @site from @from to @to",[
                    '@site' => $site->getAdminTitle(),
                    '@from' => $this->t($list[$data['from']]),
                    '@to'   => $this->t($list[$data['to']]),
                ]), 'error');
            }
        }

        $form_state->setRedirect('ucms_site.admin.site_list');
    }
}
