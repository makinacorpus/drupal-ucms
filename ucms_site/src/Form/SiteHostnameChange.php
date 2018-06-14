<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
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

    /**
     * @var SiteManager
     */
    protected $manager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * Constructor
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

        $form['#form_horizontal'] = true;

        $formData = &$form_state->getStorage();

        if (empty($formData['site'])) {
            $site = $formData['site'] = $site;
            $site->uid = $this->currentUser()->uid;
        } else {
            $site = $formData['site'];
        }
        $form['#site'] = $site; // This is used in *_form_alter()

        if (empty($formData['step'])) {
            $step = $formData['step'] = 'a';
        } else {
            $step = $formData['step'];
        }

        switch ($step) {

          case 'a':
              // Basic information about site
              return $this->buildStepA($form, $form_state, $site);
              break;

          case 'b':
              // Information about template and theme
              return $this->buildStepB($form, $form_state, $site);
              break;
        }

        // This is an error...
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
        $form['actions']['cancel'] = [
            '#markup' => l(
                $this->t("Cancel"),
                isset($_GET['destination']) ? $_GET['destination'] : 'admin/dashboard/site',
                ['attributes' => ['class' => ['btn', 'btn-danger']]]
            ),
        ];

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

        if ($this->manager->getStorage()->findByHostname($value)) {
            $form_state->setError($element, $this->t("Host name already exists"));
        }

        // Validate host name format
        if (preg_match('@[A-Z]@', $value)) {
            $form_state->setError($element, $this->t("Site name cannot contain uppercase letters"));
        }
        $regex = '@^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$@i';
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
    private function buildStepB(array $form, FormStateInterface $form_state, Site $site)
    {
        $formData = &$form_state->getStorage();
        /** @var \MakinaCorpus\Ucms\Site\Site $site */
        $site = $formData['site'];

        return confirm_form($form, $this->t("Change hostname for site %title from %hostname_current to %hostname_new?", [
            '%title' => $site->getAdminTitle(),
            '%hostname_current' => $site->http_host,
            '%hostname_new' => $formData['http_host'],
        ]), 'admin/dashboard/sites');
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

        /** @var $site Site */
        $site = $formData['site'];
        $site->http_host = $formData['http_host'];

        $this->manager->getStorage()->save($site, ['http_host']);
        drupal_set_message($this->t("Site %title hostname has been changed to %hostname", [
            '%title' => $site->getAdminTitle(),
            '%hostname' => $site->http_host,
        ]));

        $this->dispatcher->dispatch('site:hostname-change', new SiteEvent($site, $this->currentUser()->uid));

        $form_state->setRedirect('admin/dashboard/site');
    }
}
