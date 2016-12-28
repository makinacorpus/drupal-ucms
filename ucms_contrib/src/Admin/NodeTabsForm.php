<?php

namespace MakinaCorpus\Ucms\Contrib\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Contrib\ContentTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NodeTabsForm extends FormBase
{
    /**
     * @var \MakinaCorpus\Ucms\Contrib\ContentTypeManager
     */
    private $contentTypeManager;

    /**
     * {@inheritDoc}
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
        return new self($container->get('ucms_contrib.type_manager'));
    }

    /**
     * NodeTabsForm constructor.
     *
     * @param ContentTypeManager $contentTypeManager
     */
    public function __construct(ContentTypeManager $contentTypeManager)
    {
        $this->contentTypeManager = $contentTypeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['media'] = [
            '#title' => $this->t("Media tab"),
            '#type'  => 'fieldset',
        ];
        $form['media']['media_types'] = [
            '#type'          => 'checkboxes',
            '#options'       => node_type_get_names(),
            '#default_value' => $this->contentTypeManager->getMediaTypes(),
        ];

        $form['content'] = [
            '#title' => $this->t("Content tab"),
            '#type'  => 'fieldset',
        ];
        $form['content']['editorial'] = [
            '#title'         => $this->t("Editorial content types"),
            '#type'          => 'checkboxes',
            '#options'       => node_type_get_names(),
            '#default_value' => $this->contentTypeManager->getEditorialTypes(),
        ];

        $form['content']['component'] = [
            '#title'         => $this->t("Component content types"),
            '#type'          => 'checkboxes',
            '#options'       => node_type_get_names(),
            '#default_value' => $this->contentTypeManager->getComponentTypes(),
        ];

        $form['content']['locked'] = [
            '#title'         => $this->t("Locked components"),
            '#type'          => 'checkboxes',
            '#options'       => node_type_get_names(),
            '#default_value' => $this->contentTypeManager->getLockedTypes(),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'  => 'submit',
            '#value' => $this->t('Save configuration'),
        ];

        return $form;
    }

    /**
     * {@inheritDoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $components = array_filter($form_state->getValue('component'));
        $editorial = array_filter($form_state->getValue('editorial'));
        $media = array_filter($form_state->getValue('media_types'));

        // Media and content can't be both
        if (count(array_diff($media, $components, $editorial)) != count($media)) {
            $form_state->setErrorByName('component_types', $this->t("Media can't be content as well."));
        }

        // Editorial and components can't be both
        if (count(array_diff($components, $editorial)) != count($components)) {
            $form_state->setErrorByName('component_types', $this->t("Editorial content can't be components as well."));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {

        $this->contentTypeManager->setMediaTypes($form_state->getValue('media_types'));
        $this->contentTypeManager->setEditorialTypes($form_state->getValue('editorial'));
        $this->contentTypeManager->setComponentTypes($form_state->getValue('component'));
        $this->contentTypeManager->setLockedTypes($form_state->getValue('locked'));

        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
