<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Request site creation form
 */
class SiteDelete extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('event_dispatcher')
        );
    }

    private $manager;
    private $dispatcher;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(SiteManager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        if (!$site) {
            $this->logger('form')->critical("There is not site to delete!");
            return $form;
        }
        if (!$this->manager->getAccess()->userCanDelete($this->currentUser(), $site)) {
            $this->logger('form')->critical("User can't delete site!");
            return $form;
        }

        $form_state->setTemporaryValue('site', $site);
        $form['#site'] = $site; // This is used in *_form_alter()

        return confirm_form($form, $this->t("Do you want to delete site %site", ['%site' => $site->getAdminTitle()]), 'admin/dashboard/site');
    }

    /**
     * Step B form submit
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site = &$form_state->getTemporaryValue('site');

        $this->manager->getStorage()->delete($site, $this->currentUser()->id());
        drupal_set_message($this->t("Site %title has been deleted", ['%title' => $site->getAdminTitle()]));

        $form_state->setRedirect('admin/dashboard/site');
    }
}
