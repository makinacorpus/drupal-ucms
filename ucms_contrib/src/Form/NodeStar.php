<?php

namespace MakinaCorpus\Ucms\Contrib\Form;

use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Drupal\Calista\Form\AbstractEntityActionForm;

class NodeStar extends AbstractEntityActionForm
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_contrib_node_star_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $this->setEntity($form_state, $node);

        return confirm_form([], $this->t("Star %title ?", ['%title' => $node->title]), 'node/' . $node->nid);
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $this->getEntity($form_state);

        $node->is_starred = 1;
        $node->ucms_index_now = 1; // @todo find a better way
        $this->getEntityStorage('node')->save($node);

        drupal_set_message($this->t("%title has been starred.", ['%title' => $node->title]));
        $form_state->setRedirect('node/' . $node->nid);
    }
}
