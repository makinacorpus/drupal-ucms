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
class SiteRequest extends FormBase
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
        return 'ucms_site_request';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#form_horizontal'] = true;

        $formData = &$form_state->getStorage();

        if (empty($formData['site'])) {
            $site = $formData['site'] = new Site();
            $site->uid = $this->currentUser()->uid;
        } else {
            $site = $formData['site'];
        }

        if (empty($formData['step'])) {
            $step = 'a';
        } else {
            $step = $formData['step'];
        }

        switch ($step) {

          case 'a':
              return $this->buildStepA($form, $form_state, $site);
              break;

          case 'b':
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
        $form['title'] = [
            '#title'          => $this->t("Name"),
            '#type'           => 'textfield',
            '#default_value'  => $site->title,
            '#attributes'     => ['placeholder' => $this->t("Martray's optical")],
            '#description'    => $this->t("This will appear on the site as the site title"),
            '#required'       => true,
        ];

        $form['title_admin'] = [
            '#title'          => $this->t("Description"),
            '#type'           => 'textarea',
            '#default_value'  => $site->title_admin,
            '#attributes'     => ['placeholder' => $this->t("This site is about showing our glasses to our future clients")],
            '#description'    => $this->t("This will be as the site's administrative description in platform backoffice"),
            '#required'       => true,
            '#rows'           => 3,
        ];

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

        $form['replacement_of'] = [
            '#title'          => $this->t("Replaces"),
            '#type'           => 'textarea',
            '#default_value'  => $site->replacement_of,
            '#attributes'     => ['placeholder' => "martray-optique.fr"],
            '#description'    => $this->t("If the new site aims to replace an existing site, please copy/paste the site URI into this textarea, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];
        $form['http_redirects'] = [
            '#title'          => $this->t("Host name redirects"),
            '#type'           => 'textarea',
            '#default_value'  => $site->http_redirects,
            '#attributes'     => ['placeholder' => "www.martray-optique.fr, martraylunettes.fr"],
            '#description'    => $this->t("List of domain names that should redirect on this site, this is, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];

        // @todo Missing site type

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
     * Validate HTTP host (must be unique)
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
    }

    /**
     * Step B form validate
     */
    public function validateStepA(array $form, FormStateInterface $form_state)
    {
        // @todo
        //   http_host validation (unique and valid)
        //   
    }

    /**
     * Step B form submit
     */
    public function submitStepA(array $form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();
        $values   = &$form_state->getValues();

        /** @var $site Site */
        $site                 = $formData['site'];
        $site->title          = $values['title'];
        $site->title_admin    = $values['title_admin'];
        $site->http_host      = $values['http_host'];
        $site->http_redirects = $values['http_redirects'];
        $site->replacement_of = $values['replacement_of'];

        $formData['step'] = 'b';
        $form_state->setRebuild(true);
    }

    /**
     * Step B form builder
     */
    private function buildStepB(array $form, FormStateInterface $form_state, Site $site)
    {
        // @todo Form stuff

        // WARNING I won't fetch the whole Drupal 8 API in the sf_dic module,
        // this has to stop at some point, so I'll use only Drupal 7 API to
        // handle themes, this will need porting.
        $themes = list_themes();

        $options = [];
        foreach ($this->manager->getAllowedThemes() as $theme) {

            if (!isset($themes[$theme])) {
                $this->logger('default')->alert(sprintf("Theme '%s' does not exist but is referenced into sites possible selection", $theme));
                continue;
            }
            if (!$themes[$theme]->status) {
                $this->logger('default')->alert(sprintf("Theme '%s' is not enabled but is referenced into sites possible selection", $theme));
                continue;
            }

            if (isset($themes[$theme]) && file_exists($themes[$theme]->info['screenshot'])) {
                $text = theme('image', [
                    'path'        => $themes[$theme]->info['screenshot'],
                    'alt'         => $this->t('Screenshot for !theme theme', ['!theme' => $themes[$theme]->info['name']]),
                    'attributes'  => ['class' => ['screenshot']],
                ]);
            } else {
                $text = $themes[$theme]->info['name'];
            }

            $options[$theme] = $text;
        }

        $form['theme'] = [
            '#title'          => $this->t("Theme"),
            '#type'           => 'radios',
            '#options'        => $options,
            '#default_value'  => $site->theme,
            '#description'    => $this->t("This will be used for the whole site and cannot be changed once set")
        ];

        // Template site (which will be duplicated)
        // Need to create the site cloning operation first
        $form['template'] = [
            '#title'          => $this->t("Template site"),
            '#type'           => 'radios',
            '#options'        => [$this->t("Not implemented yet")],
            '#default_value'  => $site->template,
            '#disabled'       => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Request"),
        ];
        $form['actions']['back'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Go back"),
            '#submit' => ['::submitStepABack'],
        ];

        return $form;
    }

    /**
     * Step B form go back submit
     */
    public function submitStepABack(array $form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();

        /** @var $site Site */
        $site = $formData['site'];
        $site->state = SiteState::REQUESTED;
        $site->theme = $form_state->getValue('theme');
        $site->ts_created = $site->ts_changed = new \DateTime();
        // $site->template = $form_state->getValue('template');

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
        $site->state = SiteState::REQUESTED;
        $site->theme = $form_state->getValue('theme');
        // $site->template = $form_state->getValue('template');

        $this->manager->getStorage()->save($site);
        drupal_set_message($this->t("Your site creation request has been submitted"));

        $this->dispatcher->dispatch('site:request', new SiteEvent($site, $this->currentUser()->uid));

        $form_state->setRedirect('admin/dashboard/site');
    }
}
