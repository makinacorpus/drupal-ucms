<?php


namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Dashboard\Form\AbstractEntityActionForm;


class NodeMakeGlobal extends AbstractEntityActionForm
{
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
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $this->setEntity($form_state, $node);
        return confirm_form($form, $this->t("Add %title to global contents?", ['%title' => $node->title]), 'node/' . $node->nid, '');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = ucms_contrib_node_clone($this->getEntity($form_state));
        $node->site_id = null;
        $node->is_global = 1;
        $this->getEntityStorage('node')->save($node);

        drupal_set_message($this->t("%title has been added to global contents.", ['%title' => $node->title]));
        $form_state->setRedirect('node/' . $node->nid);
    }
}

