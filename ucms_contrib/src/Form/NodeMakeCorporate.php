<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Drupal\Calista\Form\AbstractEntityActionForm;

class NodeMakeCorporate extends AbstractEntityActionForm
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_node_make_corporate_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $this->setEntity($form_state, $node);
        return confirm_form($form, $this->t("Set %title as corporate content?", ['%title' => $node->title]), 'node/' . $node->nid, '');
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $this->getEntity($form_state);

        $node->is_corporate = 1;
        $node->ucms_index_now = 1; // @todo find a better way
        $this->getEntityStorage('node')->save($node);

        drupal_set_message($this->t("%title has been set as corporate content.", ['%title' => $node->title]));
        $form_state->setRedirect('node/' . $node->nid);
    }
}
