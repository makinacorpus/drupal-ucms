<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Request site creation form
 */
class SiteEdit extends FormBase
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
        return 'ucms_site_edit';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        $form['#form_horizontal'] = true;

        if (!$site) {
            $this->logger('form')->critical("There is not site to edit!");
            return $form;
        }

        $form_state->setTemporaryValue('site', $site);
        $form['#site'] = $site; // This is used in *_form_alter()

        $form['title'] = [
            '#title'          => $this->t("Name"),
            '#type'           => 'textfield',
            '#default_value'  => $site->title,
            '#attributes'     => ['placeholder' => $this->t("Martin's blog")],
            '#description'    => $this->t("This will appear as the site's title on the frontoffice."),
            '#maxlength'      => 255,
            '#required'       => true,
        ];

        $form['title_admin'] = [
            '#title'          => $this->t("Administrative title"),
            '#type'           => 'textfield',
            '#default_value'  => $site->title_admin,
            '#attributes'     => ['placeholder' => $this->t("Martin's blog")],
            '#description'    => $this->t("This will be the site's title for the backoffice."),
            '#maxlength'      => 255,
            '#required'       => true,
        ];

        $form['http_host'] = [
            '#title'            => $this->t("Host name"),
            '#type'             => 'textfield',
            '#field_prefix'     => "http://",
            '#default_value'    => $site->http_host,
            '#attributes'       => ['placeholder' => "martin-blog.fr"],
            '#description'      => $this->t("Type here the site URL"),
            '#element_validate' => ['::validateHttpHost'],
            '#required'         => true,
            '#disabled'         => !user_access(Access::PERM_SITE_GOD),
        ];

        $form['allowed_protocols'] = [
            '#title'            => $this->t("Allowed protocols"),
            '#type'             => 'select',
            '#options'          => [
                Site::ALLOWED_PROTOCOL_HTTPS  => $this->t("Secure HTTPS only"),
                Site::ALLOWED_PROTOCOL_HTTP   => $this->t("Unsecure HTTP only"),
                Site::ALLOWED_PROTOCOL_ALL    => $this->t("Both secure HTTPS and unsecure HTTP"),
                Site::ALLOWED_PROTOCOL_PASS   => $this->t("Let Drupal decide depending on the environment")
            ],
            '#default_value'    => $site->allowed_protocols,
            '#description'      => $this->t("This is a technical setting that depends on the web server configuration, the technical administrators might change it."),
            '#required'         => !user_access(Access::PERM_SITE_GOD),
        ];

        $form['replacement_of'] = [
            '#title'          => $this->t("Replaces"),
            '#type'           => 'textarea',
            '#default_value'  => $site->replacement_of,
            '#attributes'     => ['placeholder' => "martin-blog.fr"],
            '#description'    => $this->t("If the new site aims to replace an existing site, please copy/paste the site URI into this textarea, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];

        $form['http_redirects'] = [
            '#title'          => $this->t("Host name redirects"),
            '#type'           => 'textarea',
            '#default_value'  => $site->http_redirects,
            '#attributes'     => ['placeholder' => "www.my-domain.com, example.fr"],
            '#description'    => $this->t("List of domain names that should redirect on this site, this is, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];

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
                $text .= '<p>'.$themes[$theme]->info['name'].'</p>';
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

        $form['attributes']['#tree'] = true;

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Save"),
        ];
        $form['actions']['cancel'] = [
            '#markup' => l(
                $this->t("Cancel"),
                isset($_GET['destination']) ? $_GET['destination'] : 'admin/dashboard/site/' . $site->id,
                ['attributes' => ['class' => ['btn', 'btn-danger']]]
            ),
        ];

        return $form;
    }

    /**
     * Step B form submit
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site    = &$form_state->getTemporaryValue('site');
        $values  = &$form_state->getValues();

        /** @var $site Site */
        $site->title          = $values['title'];
        $site->title_admin    = $values['title_admin'];
        $site->http_redirects = $values['http_redirects'];
        $site->replacement_of = $values['replacement_of'];
        $site->http_host      = $values['http_host'];
        $site->allowed_protocols = $values['allowed_protocols'];
        $site->theme          = $values['theme'];
        $attributes = $form_state->getValue('attributes', []);
        foreach ($attributes as $name => $attribute) {
            $site->setAttribute($name, $attribute);
        }

        $this->manager->getStorage()->save($site);
        drupal_set_message($this->t("Site modifications have been saved"));

        $this->dispatcher->dispatch('site:update', new SiteEvent($site, $this->currentUser()->uid));

        $form_state->setRedirect('admin/dashboard/site/' . $site->id);
    }

    /**
     * Validate HTTP host (must be unique and valid)
     *
     * @param $element
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     */
    public function validateHttpHost(&$element, FormStateInterface $form_state)
    {
        $value = $form_state->getValue($element['#parents']);

        if (empty($value)) {
            $form_state->setError($element, $this->t("Host name cannot be empty"));

            return;
        }

        $existing = $this->manager->getStorage()->findByHostname($value);
        if ($existing && $existing->getId() != $form_state->getTemporaryValue('site')->getId()) {
            $form_state->setError($element, $this->t("Host name already exists"));
        }

        // Validate host name format
        $regex = '@^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$@i';
        if (!preg_match($regex, $value)) {
            $form_state->setError($element, $this->t("Host name contains invalid characters or has a wrong format"));
        }
    }
}
