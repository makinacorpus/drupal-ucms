<?php

namespace MakinaCorpus\Ucms\Taxo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TermDelete extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_term_delete';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, \stdClass $vocabulary = null, \stdClass $term = null)
    {
        if (!$vocabulary) {
            return $form;
        }
        if ($term === null) {
            return [];
        }

        $form_state->setTemporaryValue('vocabulary', $vocabulary);
        $form_state->setTemporaryValue('term', $term);

        $question = $this->t("Do you really want to delete the \"@name\" term?", ['@name' => $term->name]);

        return confirm_form($form, $question, 'admin/dashboard/taxonomy/'.$vocabulary->machine_name);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $vocabulary = $form_state->getTemporaryValue('vocabulary');
        $term = $form_state->getTemporaryValue('term');

        try {
            taxonomy_term_delete($term->tid);
            drupal_set_message($this->t("\"@name\" term has been deleted.", array('@name' => $term->name)));
        } catch (\Exception $e) {
            drupal_set_message($this->t("An error occured during the deletion of the \"@name\" term. Please try again.", array('@name' => $term->name)), 'error');
        }

        $form_state->setRedirect('admin/dashboard/taxonomy/'.$vocabulary->machine_name);
    }
}
