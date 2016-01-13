<?php

namespace MakinaCorpus\Ucms\Contrib\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NodeTabsForm extends FormBase
{
    /**
     * Returns a unique string identifying the form.
     *
     * @return string
     *   The unique string identifying the form.
     */
    public function getFormId()
    {
        return 'ucms_contrib_admin_structure_form';
    }

    /**
     * Form constructor.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     *
     * @return array
     *   The form structure.
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#tree'] = true;

        foreach (ucms_contrib_tab_list() as $tab => $name) {
            $form['tab'][$tab] = [
                '#title'  => t("%tab tab", ['%tab' => $name]),
                '#type'   => 'fieldset',
            ];
            $form['tab'][$tab]['types'] = [
                '#title'          => t("Content types"),
                '#type'           => 'checkboxes',
                '#options'        => node_type_get_names(),
                '#default_value'  => variable_get('ucms_contrib_tab_' . $tab .  '_type', []),
            ];
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => t('Save configuration')
        ];

        return $form;
    }

    /**
     * Form submission handler.
     *
     * @param array $form
     *   An associative array containing the structure of the form.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The current state of the form.
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        foreach ($form_state->getValue('tab') as $tab => $data) {

            // First process content types.
            $enabled = [];
            foreach ($data['types'] as $type => $status) {
                if ($status && $status === $type) {
                    $enabled[] = $type;
                }
            }

            variable_set('ucms_contrib_tab_' . $tab . '_type', $enabled);
        }

        drupal_set_message(t('The configuration options have been saved.'));
    }
}
