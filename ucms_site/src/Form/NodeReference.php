<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\NodeManager;
use MakinaCorpus\Ucms\Site\Site;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reference a node on a site
 */
class NodeReference extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.node_manager')
        );
    }

    /**
     * @var NodeManager
     */
    protected $nodeManager;

    public function __construct(NodeManager $nodeManager)
    {
        $this->nodeManager = $nodeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_node_reference';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        if (!$node) {
            $this->logger('form')->critical("There is no node to reference!");
            return $form;
        }

        // Fetch the intersection of the sites the user is webmaster and the
        // user has not this node already
        $sites = $this->nodeManager->findSiteCandidatesForReference($node, $this->currentUser()->uid);

        if (!$sites) {
            $form['notice'] = [
                '#type' => 'item',
                '#markup' => $this->t("This content is already used by all your sites."),
            ];

            return $form;
        }

        $form['#form_horizontal'] = true;

        $options = [];
        foreach ($sites as $site) {
            $options[$site->id] = $site->title;
        }

        $form_state->setTemporaryValue('node', $node);
        $form_state->setTemporaryValue('sites', $sites);

        $form['site'] = [
            '#type'           => count($options) < 11 ? 'radios' : 'select',
            '#title'          => $this->t("Select a site"),
            '#options'        => $options,
            '#required'       => true,
            '#default_value'  => null,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Use on my site"),
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
        $node   = &$form_state->getTemporaryValue('node');
        $sites  = &$form_state->getTemporaryValue('sites');
        $siteId = &$form_state->getValue('site');

        $this->nodeManager->createReference($sites[$siteId], $node);

        drupal_set_message($this->t("%title has been added to site %site", [
            '%title'  => $node->title,
            '%site'   => $sites[$siteId]->title,
        ]));

        $form_state->setRedirect('node/' . $node->nid);
    }
}
