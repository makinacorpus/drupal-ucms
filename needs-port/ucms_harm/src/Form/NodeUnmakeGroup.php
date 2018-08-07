<?php

namespace MakinaCorpus\Ucms\Harm\Form;

use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Dashboard\Form\AbstractEntityActionForm;

class NodeUnmakeGroup extends AbstractEntityActionForm
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_harm_node_unmake_group_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        $this->setEntity($form_state, $node);
        return confirm_form($form, $this->t("Remove %title from group contents?", ['%title' => $node->title]), 'node/' . $node->nid, '');
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node = $this->getEntity($form_state);

        $node->is_group = 0;
        $node->ucms_index_now = 1; // @todo find a better way
        $this->getEntityStorage('node')->save($node);

        drupal_set_message($this->t("%title has been removed from the group contents.", ['%title' => $node->title]));
        $form_state->setRedirect('node/' . $node->nid);
    }
}
