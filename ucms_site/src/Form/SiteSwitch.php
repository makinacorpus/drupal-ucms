<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
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
            $container->get('ucms_site.manager'),
            $container->get('event_dispatcher'),
            $container->get('database')
        );
    }

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @var \DatabaseConnection
     */
    private $db;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     * @param EventDispatcherInterface $dispatcher
     * @param \DatabaseConnection $db
     */
    public function __construct(SiteManager $manager, EventDispatcherInterface $dispatcher, \DatabaseConnection $db)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
        $this->db = $db;
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

        $question = $this->t(
            "Switch site @site to state @state ?",
            [
                '@site'  => $site->title,
                '@state' => $this->t(SiteState::getList()[$state]),
            ]
        );
        $form = confirm_form($form, $question, 'admin/dashboard/site/' . $site->id);

        $form['message'] = [
            '#type'       => 'textarea',
            '#title'      => $this->t("Reason"),
            '#attributes' => ['placeholder' => $this->t("Describe here why you're switching this site's state")],
        ];
      return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site = $form_state->getTemporaryValue('site');
        $state = (int)$form_state->getTemporaryValue('state');
        $data = ['from' => $site->state, 'to' => $state, 'message' => $form_state->getValue('message')];
        $list = SiteState::getList();


        $tx = null;

        try {
            $tx = $this->db->startTransaction();

            $site->state = $state;
            $this->manager->getStorage()->save($site, ['state']);
            $this->dispatcher->dispatch(SiteEvents::EVENT_SWITCH, new SiteEvent($site, $this->currentUser()->uid, $data));

            unset($tx);

            drupal_set_message(
                $this->t(
                    "Site @site has been switched from @from to @to",
                    [
                        '@site' => $site->title,
                        '@from' => $this->t($list[$data['from']]),
                        '@to'   => $this->t($list[$data['to']]),
                    ]
                )
            );

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {
                    watchdog_exception('ucms_site', $e2);
                }
                watchdog_exception('ucms_site', $e);

                drupal_set_message(
                    $this->t(
                        "There was an error switching site @site from @from to @to",
                        [
                            '@site' => $site->title,
                            '@from' => $this->t($list[$data['from']]),
                            '@to'   => $this->t($list[$data['to']]),
                        ]
                    ),
                    'error'
                );
            }
        }

        $form_state->setRedirect('admin/dashboard/site');
    }
}
