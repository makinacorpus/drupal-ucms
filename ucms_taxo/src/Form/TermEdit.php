<?php

namespace MakinaCorpus\Ucms\Taxo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TermEdit extends FormBase
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
        return 'ucms_term_edit';
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
            $term = new \stdClass();
        }

        $form_state->setTemporaryValue('vocabulary', $vocabulary);
        $form_state->setTemporaryValue('term', $term);

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#default_value' => isset($term->name) ? $term->name : '',
            '#maxlength' => 255,
            '#required' => true,
            '#weight' => -5,
        );

//        $form['description'] = array(
//            '#type' => 'text_format',
//            '#title' => $this->t('Description'),
//            '#default_value' => isset($term->description) ? $term->description : '',
//            '#format' => isset($term->format) ? $term->format : '',
//            '#weight' => 0,
//        );

        // taxonomy_get_tree and taxonomy_get_parents may contain large numbers of
        // items so we check for taxonomy_override_selector before loading the
        // full vocabulary. Contrib modules can then intercept before
        // hook_form_alter to provide scalable alternatives.
        /*
        if (!variable_get('taxonomy_override_selector', FALSE)) {
            $has_children = false;

            if (isset($term->tid)) {
                $parent = $this->manager->loadParent($term);
                $has_children = $this->manager->hasChildren($term);
            }

            $options = [];
            foreach ($this->manager->loadRootLabels() as $root_label) {
                if (!isset($label->tid) || $label->tid != $root_label->tid) {
                    $options[$root_label->tid] = $root_label->name;
                }
            }

            $form['parent'] = array(
                '#type' => 'select',
                '#title' => $this->t('Parent term'),
                '#options' => $options,
                '#empty_value' => '0',
                '#empty_option' => $this->t("- None -"),
                '#default_value' => !empty($parent) ? $parent->tid : null,
                '#multiple' => false,
            );

            if ($has_children) {
                $form['parent']['#disabled'] = true;
                $form['parent']['#description'] = $this->t("You must move or delete the children terms if you want to define a parent term for this one.");
            }
        }

        if ($this->manager->canEditLockedLabels()) {
            $form['locked'] = array(
                '#type' => 'checkbox',
                '#title' => $this->t('Non editable term'),
                '#default_value' => isset($term->is_locked) ? $term->is_locked : 0,
            );

            if (!$this->manager->canEditNonLockedLabels()) {
                $form['locked']['#disabled'] = true;
                $form['locked']['#default_value'] = 1;
            }
        }
         */

        $form['actions'] = array(
            '#type' => 'actions',
        );
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        );

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $vocabulary = $form_state->getTemporaryValue('vocabulary');
            $description = $form_state->getValue('description', []);

            $term = $form_state->getTemporaryValue('term');
            $term->name = $form_state->getValue('name');
            $term->is_locked = ($form_state->getValue('locked') === null) ? 0 : $form_state->getValue('locked');
            $term->parent = ($parent = $form_state->getValue('parent')) ? $parent : 0;
            $term->vid = $vocabulary->vid;
            $term->vocabulary_machine_name = $vocabulary->machine_name;
            $term->format = $description['format'] ?? null;
            $term->description = $description['description'] ?? null;

            $op = taxonomy_term_save($term);

            if ($op == SAVED_NEW) {
                drupal_set_message($this->t("The new \"@name\" term has been created.", array('@name' => $term->name)));
            } else {
                drupal_set_message($this->t("The \"@name\" term has been updated.", array('@name' => $term->name)));
            }
        } catch (\Exception $e) {
            drupal_set_message($this->t("An error occured during the edition of the \"@name\" term. Please try again.", array('@name' => $term->name)), 'error');
        }

        $form_state->setRedirect('admin/dashboard/taxonomy/'.$vocabulary->machine_name);
    }
}
