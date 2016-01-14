<?php

namespace MakinaCorpus\Ucms\Site\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SiteManagementForm extends FormBase
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_management_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $options = [];
        foreach (list_themes() as $theme => $data) {
            if ($data->status) {
                $options[$theme] = $data->info['name'];
            }
        }

        $form['themes'] = [
            '#title'          => $this->t("Allowed themes"),
            '#type'           => 'checkboxes',
            '#options'        => $options,
            '#default_value'  => variable_get('ucms_site_allowed_themes', []),
            '#description'    => $this->t("Themes available in the site request form to be choosen by the requester"),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => t('Save configuration')
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $enabled = [];
        foreach ($form_state->getValue('themes') as $theme => $status) {
            if ($status && $status === $theme) {
                $enabled[] = $theme;
            }
        }
        variable_set('ucms_site_allowed_themes', $enabled);

        drupal_set_message(t('The configuration options have been saved.'));
    }
}
