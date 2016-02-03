<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NodeUnflag extends FormBase
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_node_publish_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $form_state->setTemporaryValue('node', $node);

        return confirm_form([], $this->t("Un-flag %title ?", ['%title' => $node->title]), 'node/' . $node->nid);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $form_state->getTemporaryValue('node');
        $node->is_flagged = 0;
        node_save($node);

        drupal_set_message($this->t("%title has been un-flagged.", ['%title' => $node->title]));
        $form_state->setRedirect('node/' . $node->nid);
    }
}