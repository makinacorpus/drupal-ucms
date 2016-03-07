<?php

namespace MakinaCorpus\Ucms\Contrib\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeTabsForm extends FormBase
{
    /**
     * @var \MakinaCorpus\Ucms\Contrib\TypeHandler
     */
    private $typeHandler;

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
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_contrib.type_handler'));
    }

    /**
     * NodeTabsForm constructor.
     *
     * @param TypeHandler $typeHandler
     */
    public function __construct(TypeHandler $typeHandler) {
        $this->typeHandler = $typeHandler;
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

        foreach ($this->typeHandler->getTabs() as $tab => $name) {
            $form['tab'][$tab] = [
                '#title'  => $this->t("%tab tab", ['%tab' => $this->t($name)]),
                '#type'   => 'fieldset',
            ];
            $form['tab'][$tab]['types'] = [
                '#title'          => $this->t("Content types"),
                '#type'           => 'checkboxes',
                '#options'        => node_type_get_names(),
                '#default_value'  => $this->typeHandler->getTabTypes($tab),
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

            $this->typeHandler->setTabTypes($tab, $enabled);
        }

        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
