<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MakinaCorpus\Ucms\Site\NodeManager;

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
            $container->get('ucms_site.node_manager')
        );
    }

    private $siteManager;
    private $nodeManager;

    /**
     * Constructor
     *
     * @param SiteManager $siteManager
     * @param NodeManager $nodeManager
     */
    public function __construct(SiteManager $siteManager, NodeManager $nodeManager)
    {
        $this->siteManager = $siteManager;
        $this->nodeManager = $nodeManager;
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
        $user         = $this->currentUser();
        $canDoGlobal  = $user->hasPermission(Access::PERM_CONTENT_MANAGE_GLOBAL) || $user->hasPermission(Access::PERM_CONTENT_MANAGE_GROUP);

        if (!$type) {
            $this->logger('form')->critical("No content type provided.");
            return $form;
        }

        $form_state->setTemporaryValue('type', $type);

        // Load the sites the user is webmaster or contributor
        $roles = $this->siteManager->getAccess()->getUserRoles($user);
        $sites = $this->siteManager->getStorage()->loadAll(array_keys($roles));

        $options = [];
        foreach ($sites as $site) {
            // @todo should be done using node_access somehow ?
            if (in_array($site->state, [SiteState::INIT, SiteState::OFF, SiteState::ON])) {
                $options[$site->id] = check_plain($site->title);
            }
        }


        $form['action'] = [
            '#title'          => $this->t("Where would you want to create this content ?"),
            '#type'           => 'radios',
            '#options'        => [
                'global'      => $this->t("Create a global content"),
                'local'       => $this->t("Create a content in one of my sites"),
            ],
            '#default_value'  => 'local',
        ];

        // First set the site select if available, we will later add the #states
        // property whenever it make sense
        if ($options) {
            $form['site'] = [
                '#type'           => count($options) < 11 ? 'radios' : 'select',
                '#title'          => $this->t("Select a site"),
                '#options'        => $options,
                // If "create a global content" is selected, this widget does
                // not serve any purpose, so we don't care about default value
                '#default_value'  => key($options),
                '#required'       => true,
            ];
        }

        if (empty($options)) {
            if (!$canDoGlobal) {
                throw new \LogicException("User cannot create content, this form should not be displayed");
            }

            // User may only create global content, just set a message
            $form['help'] = [
                '#markup' => $this->t("Do you want to create a global content ?"),
                '#prefix' => '<p>',
                '#suffix' => '</p>',
            ];
            $form['action'] = ['#type' => 'value', '#value' => 'global'];
        } else {
            if ($canDoGlobal) {
                $form['site']['#states'] = ['visible' => [':input[name="action"]' => ['value' => 'local']]];
            } else {
                $form['action'] = ['#type' => 'value', '#value' => 'local'];
            }
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'     => 'submit',
            '#value'    => $this->t("Continue"),
            '#weight'   => -20,
        ];
        if (isset($_GET['destination'])) {
            $form['actions']['cancel'] = [
                '#markup' => l(
                    $this->t("Cancel"),
                    $_GET['destination'],
                    ['attributes' => ['class' => ['btn', 'btn-danger']]]
                ),
            ];
        }

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $type   = $form_state->getTemporaryValue('type');
        $action = $form_state->getValue('action');
        $siteId = $form_state->getValue('site');
        $path   = 'node/add/' . str_replace('_', '-', $type);

        $options = [];
        switch ($action) {

            case 'local':
                list($path, $options) = $this->siteManager->getUrlInSite($siteId, $path);
                drupal_set_message($this->t("You are now creating a content into your site."));
                break;

            default:
                drupal_set_message($this->t("You are now creating a global content."));
                break;
        }

        $form_state->setRedirect($path, $options);
    }

    public function redirect(array &$form, FormStateInterface $form_state)
    {
        $type = $form_state->getTemporaryValue('type');
        $form_state->setRedirect('node/add/' . str_replace('_', '-', $type));
    }
}
