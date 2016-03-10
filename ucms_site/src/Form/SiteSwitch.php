<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
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


        $tx = null;

        try {
            $tx = $this->db->startTransaction();

            $site->state = $state;
            $this->manager->getStorage()->save($site, ['state']);
            $this->dispatcher->dispatch('site:switch', new SiteEvent($site, $this->currentUser()->uid, $data));

            unset($tx);

            drupal_set_message(
                $this->t(
                    "Site @site has been switched from @from to @to",
                    [
                        '@site' => $site->title,
                        '@from' => $list[$data['from']],
                        '@to'   => $list[$data['to']],
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
                        "Site @site has been switched from @from to @to",
                        [
                            '@site' => $site->title,
                            '@from' => $list[$data['from']],
                            '@to'   => $list[$data['to']],
                        ]
                    ),
                    'error'
                );
            }
        }

        $form_state->setRedirect('admin/dashboard/site');
    }
}
