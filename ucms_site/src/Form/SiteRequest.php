<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Url;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\LinkGeneratorTrait;
use MakinaCorpus\Ucms\Dashboard\Form\FormHelper;
use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SiteRequest extends FormBase
{
    use LinkGeneratorTrait;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new static(
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
        return 'ucms_site_request';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $formData = &$form_state->getStorage();

        if (empty($formData['site'])) {
            $site = $formData['site'] = new Site();
            $site->uid = $this->currentUser()->id();
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
        $form['title'] = [
            '#title'          => $this->t("Name"),
            '#type'           => 'textfield',
            '#default_value'  => $site->getTitle(),
            '#attributes'     => ['placeholder' => $this->t("Martin's blog")],
            '#description'    => $this->t("This will appear as the site's title on the frontoffice."),
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
            '#required'         => true,
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
            '#attributes'     => ['placeholder' => "www.martin-blog.fr, martinblog.com"],
            '#description'    => $this->t("List of domain names that should redirect on this site, this is, you may write more than one URI or any useful textual information."),
            '#required'       => false,
            '#rows'           => 2,
        ];

        $form['is_public'] = [
            '#title'         => $this->t("Public site"),
            '#type'          => 'checkbox',
            '#description'   => $this->t("Uncheck this field to limit access to the site."),
            '#default_value' => ($site->getId() == null) ? 1 : $site->isPublic(),
        ];

        // @todo Missing site type

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

        if ($this->manager->getStorage()->findByHostname($value)) {
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
     * Step A form validate
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
        $site->allowed_protocols = $values['allowed_protocols'];
        $site->http_redirects = $values['http_redirects'];
        $site->replacement_of = $values['replacement_of'];
        $site->is_public      = $values['is_public'];

        $formData['step'] = 'b';
        $form_state->setRebuild(true);
    }

    /**
     * Step B form builder
     */
    private function buildStepB(array $form, FormStateInterface $form_state, Site $site)
    {
        $options = [];
        foreach ($this->manager->getAllowedThemes() as $theme) {
            if (!$this->themeHandler->themeExists($theme)) {
                $this->logger('default')->alert(\sprintf("Theme '%s' does not exist or is not installed yet is referenced into sites possible selection", $theme));
                continue;
            }
            $options[$theme] = $this->themeHandler->getName($theme);
        }

        $defaultTheme = null;
        if ($theme = $site->getTheme()) {
            $defaultTheme = $theme;
        } elseif (\count($options ?? []) == 1) {
            \reset($options);
            $defaultTheme = \key($options);
        }

        $form['theme'] = [
            '#title'         => $this->t("Theme"),
            '#type'          => 'radios',
            '#options'       => $options,
            '#default_value' => $defaultTheme,
            '#required'      => true,
            '#disabled'      => (\count($options ?? []) == 1),
        ];

        // Is template site
        $currentUser = $this->currentUser();

        $canManage = (
            $currentUser->hasPermission(Access::PERM_SITE_MANAGE_ALL) ||
            $currentUser->hasPermission(Access::PERM_SITE_GOD)
        );

        $form['is_template'] = [
            '#title'         => $this->t("Is template site?"),
            '#type'          => 'radios',
            '#options'       => [1 => $this->t("Yes"), 0 => $this->t("No")],
            '#access'        => $canManage,
            '#default_value' => $site->isTemplate(),
        ];

        // Template site (which will be duplicated)
        $templateList = $this->manager->getTemplateList();

        if ($canManage) {
            \array_unshift($templateList, $this->t('- None -'));
        }

        $defaultTemplate = null;
        if ($templateId = $site->getTemplateId()) {
            $defaultTemplate = $templateId;
        } elseif (\count($templateList ?? []) == 1) {
            \reset($templateList);
            $defaultTemplate = \key($templateList);
        }

        $form['template_id'] = [
            '#title'         => $this->t("Template site"),
            '#type'          => 'radios',
            '#options'       => $templateList,
            '#default_value' => $defaultTemplate,
            '#required'      => !$canManage && $templateList,
            '#access'        => !empty($templateList),
            '#disabled'      => (\count($templateList) == 1),
        ];

        if ($form['is_template']['#access']) {
            $form['template_id']['#states'] = [
                'visible' => [':input[name="is_template"]' => ['value' => 0]],
            ];
        }

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
                '#default_value'      => null,
                '#required'           => false,
            ];
        }
         */

        $form['attributes']['#tree'] = true;

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
        $site->template_id = $form_state->getValue('is_template') ? 0 : $form_state->getValue('template_id');
        $site->is_template = $form_state->getValue('is_template');
        $hashMap = @\json_decode($form_state->getValue('favicon')['fid'],true);
        if (\count($hashMap ?? [])){
            $site->favicon = \array_keys($hashMap)[0];
        }

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
        $site->template_id = $form_state->getValue('is_template') ? 0 : $form_state->getValue('template_id');
        $site->is_template = $form_state->getValue('is_template');
        $hashMap = @\json_decode($form_state->getValue('favicon')['fid'],true);
        if (\count($hashMap ?? [])){
            $site->favicon = \array_keys($hashMap)[0];
        }
        $attributes = $form_state->getValue('attributes', []);
        foreach ($attributes as $name => $attribute) {
            $site->setAttribute($name, $attribute);
        }

        if ($site->template_id) {
            $site->type = $this->manager->getStorage()->findOne($site->getTemplateId())->getType();
        }

        $this->manager->getStorage()->save($site);
        // @todo fixme
        \drupal_set_message($this->t("Your site creation request has been submitted"));

        $this->dispatcher->dispatch('site:request', new SiteEvent($site, $this->currentUser()->id()));

        $form_state->setRedirect('ucms_site.admin.site_list');
    }
}
