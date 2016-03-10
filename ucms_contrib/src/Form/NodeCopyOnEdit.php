<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\NodeDispatcher;
use MakinaCorpus\Ucms\Site\Site;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Copy a node on a site
 */
class NodeCopyOnEdit extends FormBase
{
    /**
     * @var NodeDispatcher
     */
    private $nodeDispatcher;

    /**
     * {@inheritDoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.node_dispatcher'),
            $container->get('module_handler')
        );
    }

    /**
     * NodeCopyOnEdit constructor.
     *
     * @param NodeDispatcher $dispatcher
     * @param ModuleHandlerInterface $moduleHandler
     */
    public function __construct(NodeDispatcher $dispatcher, ModuleHandlerInterface $moduleHandler)
    {
        $this->nodeDispatcher = $dispatcher;
        $this->ssoEnabled = $moduleHandler ? $moduleHandler->moduleExists('ucms_sso') : false;
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
        $form['#form_horizontal'] = true;

        if (!$node) {
            $this->logger('form')->critical("There is no node to reference!");

            return $form;
        }

        // Fetch the intersection of the sites the user is webmaster and the
        // user has not this node already
        $sites = $this->nodeDispatcher->findSiteCandidatesForCloning($node, $this->currentUser()->uid);

        if (!$sites) {
            drupal_set_message($this->t("This content is already in all your sites"));

            return $form;
        }

        $options = [];
        foreach ($sites as $site) {
            $options[$site->id] = $site->title;
        }

        $form_state->setTemporaryValue('node', $node);
        $form_state->setTemporaryValue('sites', $sites);

        $form['site'] = [
            '#type'          => count($options) < 11 ? 'radios' : 'select',
            '#title'         => $this->t("Select a site"),
            '#options'       => $options,
            '#required'      => true,
            '#default_value' => null,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'  => 'submit',
            '#value' => $this->t("Add it to my site"),
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
     * Step B form submit
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = &$form_state->getTemporaryValue('node');
        $sites = &$form_state->getTemporaryValue('sites');
        $siteId = &$form_state->getValue('site');
        /* @var Site */
        $site = $sites[$siteId];

        drupal_set_message($this->t("You can now edit this node on this site, it will be automatically duplicated."));

        if ($this->ssoEnabled) {
            $uri = url('sso/goto/'.$site->id);
        } else {
            $uri = url('http://'.$site->http_host);
        }

        $options = ['query' => ['destination' => 'node/'.$node->id().'/clone']];
        $form_state->setRedirect($uri, $options);
    }
}
