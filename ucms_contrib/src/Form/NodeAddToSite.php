<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Choose the site in which add the new node.
 */
class NodeAddToSite extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('module_handler')
        );
    }


    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * @var boolean
     */
    private $ssoEnabled;


    /**
     * Constructor
     *
     * @param SiteManager $siteManager
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(SiteManager $siteManager, ModuleHandlerInterface $moduleHandler)
    {
        $this->siteManager = $siteManager;
        $this->ssoEnabled = $moduleHandler ? $moduleHandler->moduleExists('ucms_sso') : false;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_node_add_to_site_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $type = null)
    {
        if (!$type) {
            $this->logger('form')->critical("No content type provided.");
            return $form;
        }

        $form_state->setTemporaryValue('type', $type);

        // Load the sites the user is webmaster or contributor
        $roles = $this->siteManager->getAccess()->getUserRoles($this->currentUser());
        $sites = $this->siteManager->getStorage()->loadAll(array_keys($roles));

        $form['#form_horizontal'] = true;

        $options = [];
        foreach ($sites as $site) {
            // Prevent creating content on disabled or pending sites
            if (in_array($site->state, [SiteState::OFF, SiteState::ON])) {
                $options[$site->id] = check_plain($site->title);
            } else {
                unset($sites[$site->id]);
            }
        }

        $form_state->setTemporaryValue('sites', $sites);

        $form['site'] = [
            '#type'     => (count($options) <= 10) ? 'radios' : 'select',
            '#title'    => $this->t("Select a site"),
            '#options'  => $options,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'     => 'submit',
            '#value'    => $this->t("Continue"),
            '#weight'   => -20,
        ];

        if (
            $this->currentUser()->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL) ||
            $this->currentUser()->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP)
        ) {
            $form['actions']['redirect'] = [
                '#type'     => 'submit',
                '#value'    => $this->t("Create a global content"),
                '#validate' => [],
                '#submit'   => ['::redirect'],
                '#weight'   => -10,
            ];
        }

        return $form;
    }


    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        if (!$form_state->getValue('site')) {
            $form_state->setErrorByName('site', $this->t("Please choose the site for which you want to create a new content."));
        }
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $type   = $form_state->getTemporaryValue('type');
        $sites  = $form_state->getTemporaryValue('sites');
        /* @var Site */
        $site   = $sites[$form_state->getValue('site')];

        $options = ['query' => ['destination' => 'node/add/' . str_replace('_', '-', $type)]];

        if ($this->ssoEnabled) {
            $uri = url('sso/goto/' . $site->id);
            if ($_GET['destination']) {
                // Keep your brain ahead, and forward new redirection with any
                // another parameter for SSO mechanism. Because otherwise, we
                // would have a destination conflict.
                $options['query']['form_redirect'] = $_GET['destination'];
                // © Allô maman... ca va pas fort... j'ai peur du temps...
                // © Allô maman... j'aime pas tes rides et tes cheveux blancs
                // © Allô maman... bobooooooo... bobooooooooooo...
                // © Allons-nous en!!
                // @see drupal_goto()
                unset($_GET['destination']);
            }
        } else {
            $uri = url('http://' . $site->http_host);
        }


        $form_state->setRedirect($uri, $options);
    }


    public function redirect(array &$form, FormStateInterface $form_state)
    {
        $type = $form_state->getTemporaryValue('type');
        $form_state->setRedirect('node/add/' . str_replace('_', '-', $type));
    }
}
