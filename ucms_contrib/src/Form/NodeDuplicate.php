<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copy a node on a site
 */
class NodeDuplicate extends FormBase
{
    /**
     * @var NodeManager
     */
    private $nodeManager;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * {@inheritDoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new static(
            $container->get('ucms_site.node_manager'),
            $container->get('ucms_site.manager'),
            $container->get('module_handler')
        );
    }

    /**
     * Constructor.
     *
     * @param NodeManager $nodeManager
     * @param SiteManager $siteManager
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(NodeManager $nodeManager, SiteManager $siteManager, ModuleHandlerInterface $moduleHandler)
    {
        $this->nodeManager = $nodeManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_node_copy_on_edit_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $account      = $this->currentUser();
        $candidates   = $this->nodeManager->findSiteCandidatesForCloning($node, $this->currentUser()->id());
        $siteContext  = $this->siteManager->hasContext() ? $this->siteManager->getContext() : null;
        $isNodeInSite = $siteContext && ($node->site_id == $siteContext->getId());
        $canEditAll   = $node->access(Access::OP_UPDATE, $account);
        $canDuplicate = $candidates && !$isNodeInSite && $this->nodeManager->getAccessService()->userCanDuplicate($account, $node);

        $form_state->setTemporaryValue('node', $node);

        if ($canEditAll && $canDuplicate) {
            $form['action'] = [
                '#type'     => 'radios',
                '#options'  => [],
                '#required' => true,
            ];
        } else {
            $form['action'] = [
                '#type'     => 'value',
                '#value'    => $canDuplicate ? 'duplicate' : 'edit',
            ];
            if ($canDuplicate) {
                $form['help'] = [
                    '#markup' => $this->t("Do you want to duplicate this content within your site ?"),
                    '#prefix' => '<p>',
                    '#suffix' => '</p>',
                ];
            } else {
                $form['help'] = [
                    '#markup' => $this->t("Do you want to edit this content ?"),
                    '#prefix' => '<p>',
                    '#suffix' => '</p>',
                ];
            }
        }

        if ($canDuplicate && (!$isNodeInSite || $candidates)) {

            if ('value' !== $form['action']['#type']) {
                $form['action']['#options']['duplicate'] = $this->t("Duplicate in my site");
                $form['action']['#default_value'] = 'duplicate';
            }

            // If duplicate is possible, let the user choose the site.
            if ($siteContext) {
                $form['site'] = ['#type' => 'value', '#value' => $siteContext->getId()];
            } else {
                $options = [];
                foreach ($candidates as $site) {
                    $options[$site->id] = check_plain($site->title);
                }
                $form['site'] = [
                    '#type'           => count($options) < 11 ? 'radios' : 'select',
                    '#title'          => $this->t("Select a site"),
                    '#options'        => $options,
                    '#default_value'  => null,
                    '#states'         => ['visible' => [':input[name="action"]' => ['value' => 'duplicate']]],
                ];
            }
        }

        if ($canEditAll && 'value' !== $form['action']['#type']) {
            // Duplicate action superseed the 'edit' action, which means that
            // by the order of execution, default will always be 'duplicate'
            // whenever the user can.
            $form['action']['#options']['edit'] = $this->t("Edit the content globally (affects all site the content is in)");
            $form['action']['#default_value'] = 'edit';
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Continue"),
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

    /**
     * Duplicate in context submit.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node   = $form_state->getTemporaryValue('node');
        $action = $form_state->getValue('action');
        $siteId = $form_state->getValue('site');

        $options = [];
        if (isset($_GET['destination'])) {
            $options['query']['destination'] = $_GET['destination'];
            unset($_GET['destination']);
        }

        switch ($action) {

            case 'duplicate':
                list($path, $options) = $this->siteManager->getUrlInSite($siteId, 'node/' . $node->id(). '/clone', $options);
                drupal_set_message($this->t("You can now edit this node on this site, it will be automatically duplicated."));
                break;

            default:
                $path = 'node/' . $node->id() . '/edit';
                drupal_set_message($this->t("You are now editing the global content for all sites."));
                break;
        }

        $form_state->setRedirect($path, $options);
    }
}
