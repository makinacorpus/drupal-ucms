<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NodeUnstar extends FormBase
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_node_unstar_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $form_state->setTemporaryValue('node', $node);

        return confirm_form([], $this->t("Unstar %title ?", ['%title' => $node->title]), 'node/' . $node->nid);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $form_state->getTemporaryValue('node');
        $node->is_starred = 0;
        $node->ucms_index_now = 1; // @todo find a better way
        node_save($node);

        drupal_set_message($this->t("%title has been unstarred.", ['%title' => $node->title]));
        $form_state->setRedirect('node/' . $node->nid);
    }
}