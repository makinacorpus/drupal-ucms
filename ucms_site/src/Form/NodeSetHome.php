<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reference a node on a site
 */
class NodeSetHome extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager')
        );
    }

    /**
     * @var SiteManager
     */
    protected $siteManager;

    public function __construct(SiteManager $siteManager)
    {
        $this->siteManager = $siteManager;
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
            $this->logger('form')->critical("There is no node!");
            return $form;
        }

        $form_state->setTemporaryValue('node', $node);

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Set as home page"),
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
        /** @var \Drupal\node\NodeInterface $node */
        $node = &$form_state->getTemporaryValue('node');
        $site = $this->siteManager->getContext();
        $site->home_nid = $node->id();

        $this->siteManager->getStorage()->save($site, ['home_nid']);

        $form_state->setRedirect('node/' . $node->id());
    }
}
