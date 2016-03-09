<?php

namespace MakinaCorpus\Ucms\Layout\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class ThemeRegionsForm extends FormBase
{
    use StringTranslationTrait;

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
            '#type' => 'item',
            '#title' => $this->t("Enable regions"),
            '#tree' => true,
            //'#collapsible' => false,
            '#description' => t("Disable all regions if you do not with layouts to be usable with this theme."),
        ];

        foreach ($all as $key => $label) {
            $form['regions'][$key] = [
                '#type' => 'select',
                '#title' => $label,
                '#options' => [
                    UCMS_REGION_DISABLED => $this->t("Disabled"),
                    UCMS_REGION_PAGE_CONTEXT => $this->t("Page context"),
                    UCMS_REGION_SITE_CONTEXT => $this->t("Site context"),
                ],
                '#default_value' => isset($enabled[$key]) ? $enabled[$key] : UCMS_REGION_DISABLED,
            ];
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t('Save configuration')
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Save enabled regions only.
        $enabled = array_filter($form_state->getValue('regions'));
        variable_set('ucms_layout_regions_' . $form['#theme_key'], $enabled);
        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
