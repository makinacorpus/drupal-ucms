<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Dashboard\Form\FormHelper;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SiteHostnameChange extends FormBase
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

    protected $dispatcher;
    protected $site;
    protected $siteManager;

    /**
     * Constructor
     */
    public function __construct(SiteManager $siteManager, EventDispatcherInterface $dispatcher)
    {
        $this->siteManager = $siteManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_hostname_change';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        if (!$site) {
            return $form;
        }

        $this->site = $site;
        $formData = &$form_state->getStorage();
        $step = $formData['step'] ?? 'a';

        switch ($step) {
          case 'a':
              return $this->buildStepA($form, $form_state, $this->site);
          case 'b':
              return $this->buildStepB($form, $form_state);
        }

        $this->logger('form')->critical("Invalid step @step", ['@step' => $step]);

        return $form;
    }

    /**
     * Step A form builder
     */
    private function buildStepA(array $form, FormStateInterface $form_state, Site $site)
    {
        $form['http_host'] = [
            '#title'            => $this->t("Host name"),
            '#type'             => 'textfield',
            '#field_prefix'     => "http://",
            '#default_value'    => $site->http_host,
            '#attributes'       => ['placeholder' => "martray-optique.fr"],
            '#description'      => $this->t("Type here the site URL"),
            '#element_validate' => ['::validateHttpHost'],
            '#required'         => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Continue"),
            '#submit' => ['::submitStepA'],
        ];
        $form['actions']['cancel'] = FormHelper::createCancelLink(new Url('ucms_site.admin.site_list'));

        return $form;
    }

    /**
     * Validate HTTP host (must be unique and valid)
     */
    public function validateHttpHost(&$element, FormStateInterface $form_state, &$complete_form)
    {
        $value = $form_state->getValue($element['#parents']);

        if (empty($value)) {
            $form_state->setError($element, $this->t("Host name cannot be empty"));
            return;
        }

        if ($this->siteManager->getStorage()->findByHostname($value)) {
            $form_state->setError($element, $this->t("Host name already exists"));
        }

        // Validate host name format
        if (preg_match('@[A-Z]@', $value)) {
            $form_state->setError($element, $this->t("Site name cannot contain uppercase letters"));
        }
        $regex = '@^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$@';
        if (!preg_match($regex, $value)) {
            $form_state->setError($element, $this->t("Host name contains invalid characters or has a wrong format"));
        }
    }

    /**
     * Step B form submit
     */
    public function submitStepA(array $form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();
        $formData['http_host'] = $form_state->getValue('http_host');
        $formData['step'] = 'b';

        $form_state->setRebuild(true);
    }

    /**
     * Step B form builder
     */
    private function buildStepB(array $form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();

        $form['#title'] = $this->t("Change hostname for site %title from %hostname_current to %hostname_new?", [
            '%title' => $this->site->getAdminTitle(),
            '%hostname_current' => $this->site->getHostname(),
            '%hostname_new' => $formData['http_host'],
        ]);

        $form['#attributes']['class'][] = 'confirmation';
        $form['description'] = ['#markup' => $this->t("This action cannot be undone.")];
        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t("Confirm"),
            '#button_type' => 'primary',
        ];

        $form['actions']['cancel'] = FormHelper::createCancelLink(new Url('ucms_site.admin.site_list'));

        if (!isset($form['#theme'])) {
            $form['#theme'] = 'confirm_form';
        }

        return $form;
    }

    /**
     * Step B form go back submit
     */
    public function submitStepABack(array $form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();
        $formData['step'] = 'a';
        $form_state->setRebuild(true);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();

        $this->site->http_host = $formData['http_host'];

        $this->siteManager->getStorage()->save($this->site, ['http_host']);
        \drupal_set_message($this->t("Site %title hostname has been changed to %hostname", [
            '%title' => $this->site->getAdminTitle(),
            '%hostname' => $this->site->getHostname(),
        ]));

        $this->dispatcher->dispatch('site:hostname-change', new SiteEvent($this->site, $this->currentUser()->id()));

        $form_state->setRedirect('ucms_site.admin.site_list');
    }
}
