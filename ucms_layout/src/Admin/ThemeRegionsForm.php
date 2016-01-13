<?php

namespace MakinaCorpus\Ucms\Layout\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ThemeRegionsForm extends FormBase
{
    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_layout_admin_theme_region_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $theme = null)
    {
        if (!$theme) {
            return $form;
        }

        $form['#theme_key'] = $theme;

        $all      = system_region_list($theme);
        $enabled  = ucms_layout_theme_region_list($theme);

        $form['regions'] = [
            '#title'          => t("Enabled regions"),
            '#type'           => 'checkboxes',
            '#options'        => $all,
            '#default_value'  => $enabled,
            '#description'    => t("Uncheck all regions if you do not with layouts to be usable with this theme."),
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
        // Save enabled regions.
        $enabled = [];
        foreach ($form_state->getValue('regions') as $region => $status) {
            if ($status && $status === $region) {
                $enabled[] = $region;
            }
        }
        variable_set('ucms_layout_regions_' . $form['#theme_key'], $enabled);

        drupal_set_message(t('The configuration options have been saved.'));
    }
}
