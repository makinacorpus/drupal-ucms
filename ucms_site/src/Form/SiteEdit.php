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
use Drupal\Core\Extension\ThemeHandlerInterface;

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
            $container->get('event_dispatcher'),
            $container->get('theme_handler')
        );
    }

    protected $manager;
    protected $dispatcher;
    protected $themeHandler;

    public function __construct(SiteManager $manager, EventDispatcherInterface $dispatcher, ThemeHandlerInterface $themeHandler)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
        $this->themeHandler = $themeHandler;
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
        if (!$site) {
            $this->logger('form')->critical("There is not site to edit!");
            return $form;
        }

        $form_state->setTemporaryValue('site', $site);
        $form['#site'] = $site; // This is used in *_form_alter()

        $form['title'] = [
            '#title'          => $this->t("Name"),
            '#type'           => 'textfield',
            '#default_value'  => $site->getTitle(),
            '#attributes'     => ['placeholder' => $this->t("Martin's blog")],
            '#description'    => $this->t("This will appear as the site's title on the frontoffice."),
            '#maxlength'      => 255,
            '#required'       => true,
        ];

        $form['title_admin'] = [
            '#title'          => $this->t("Administrative title"),
            '#type'           => 'textfield',
            '#default_value'  => $site->getRawAdminTitle(),
            '#attributes'     => ['placeholder' => $this->t("Martin's blog")],
            '#description'    => $this->t("This will be the site's title for the backoffice."),
            '#maxlength'      => 255,
            '#required'       => true,
        ];

        $form['http_host'] = [
            '#title'            => $this->t("Host name"),
            '#type'             => 'textfield',
            '#field_prefix'     => "http://",
            '#default_value'    => $site->getHostname(),
            '#attributes'       => ['placeholder' => "martin-blog.fr"],
            '#description'      => $this->t("Type here the site URL"),
            '#element_validate' => ['::validateHttpHost'],
            '#required'         => true,
            '#disabled'         => true, // !user_access(Access::PERM_SITE_GOD),
        ];

        $form['allowed_protocols'] = [
            '#title'            => $this->t("Allowed protocols"),
            '#type'             => 'select',
            '#options'          => [
                Site::ALLOWED_PROTOCOL_PASS   => $this->t("Let Drupal decide"),
                Site::ALLOWED_PROTOCOL_HTTPS  => $this->t("Secure HTTPS only"),
                Site::ALLOWED_PROTOCOL_HTTP   => $this->t("Unsecure HTTP only"),
            ],
            '#default_value'    => $site->getAllowedProtocols(),
            '#description'      => $this->t("This is a technical setting that depends on the web server configuration, the technical administrators might change it."),
            '#required'         => true, // !user_access(Access::PERM_SITE_GOD),
        ];

        $form['replacement_of'] = [
            '#title'          => $this->t("Replaces"),
            '#type'           => 'textarea',
            '#default_value'  => $site->getReplacementOf(),
            '#attributes'     => ['placeholder' => "martin-blog.fr"],
            '#description'    => $this->t("If the new site aims to replace an existing site, please copy/paste the site URI into this textarea, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];

        $form['http_redirects'] = [
            '#title'          => $this->t("Host name redirects"),
            '#type'           => 'textarea',
            '#default_value'  => $site->getHttpRedirects(),
            '#attributes'     => ['placeholder' => "www.my-domain.com, example.fr"],
            '#description'    => $this->t("List of domain names that should redirect on this site, this is, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];

        $options = [];
        foreach ($this->manager->getAllowedThemes() as $theme) {
            if (!$this->themeHandler->themeExists($theme)) {
                $this->logger('default')->alert(\sprintf("Theme '%s' does not exist or is not installed yet is referenced into sites possible selection", $theme));
                continue;
            }
            $options[$theme] = $this->themeHandler->getName($theme);
        }

        $form['theme'] = [
            '#title'          => $this->t("Theme"),
            '#type'           => 'radios',
            '#options'        => $options,
            '#default_value'  => $site->getTheme(),
        ];

        // Favicon
        /*
         * FIXME
         *
        $useFavicon = variable_get('ucms_site_use_custom_favicon', false);
        if ($useFavicon) {
            $form['favicon'] = [
                '#title'              => $this->t("Favicon"),
                '#type'               => 'file_chunked',
                '#upload_validators'  => [''],
                '#field types'        => ['image'],
                '#multiple'           => false,
                '#default_value'      => $site->getFavicon(),
                '#required'           => false,
            ];
        }
         */

        $form['attributes']['#tree'] = true;

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Save"),
        ];
        /*
         * FIXME?
         *
        $form['actions']['cancel'] = [
            '#markup' => l(
                $this->t("Cancel"),
                isset($_GET['destination']) ? $_GET['destination'] : 'admin/dashboard/site/' . $site->getId(),
                ['attributes' => ['class' => ['btn', 'btn-danger']]]
            ),
        ];
         */

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
        /*
         * FIXME
         *
        $hashmap = @json_decode($values['favicon']['fid'],true);
        if (count($hashmap)){
            $site->favicon = array_keys($hashmap)[0];
        }
         */
        $attributes = $form_state->getValue('attributes', []);
        foreach ($attributes as $name => $attribute) {
            $site->setAttribute($name, $attribute);
        }

        $this->manager->getStorage()->save($site);
        \drupal_set_message($this->t("Site modifications have been saved"));

        $this->dispatcher->dispatch('site:update', new SiteEvent($site, $this->currentUser()->id()));

        $form_state->setRedirect('ucms_site.admin.site_list');
    }

    /**
     * Validate HTTP host (must be unique and valid)
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
        if (preg_match('@[A-Z]@', $value)) {
            $form_state->setError($element, $this->t("Site name cannot contain uppercase letters"));
        }
        $regex = '@^(([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*([a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$@';
        if (!preg_match($regex, $value)) {
            $form_state->setError($element, $this->t("Host name contains invalid characters or has a wrong format"));
        }
    }
}
