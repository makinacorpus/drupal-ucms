<?php


namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Site\NodeManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeMakeGlobal extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.node_manager')
        );
    }

    public function __construct(NodeManager $nodeManager)
    {
        $this->nodeManager = $nodeManager;
    }

    private $nodeManager;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_node_make_global_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, NodeInterface $node = null)
    {
        $formState->setTemporaryValue('node', $node);

        return confirm_form($form, $this->t("Add %title to global contents?", ['%title' => $node->title]), 'node/' . $node->id(), '');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $original = $formState->getTemporaryValue('node');

        $node = $this->nodeManager->createAndSaveClone($original, [
            'uid'       => $this->currentUser()->id(),
            'site_id'   => null,
            'is_global' => 1,
        ]);

        drupal_set_message($this->t("%title has been added to global contents.", ['%title' => $node->title]));
        $formState->setRedirect('node/' . $node->id());
    }
}
