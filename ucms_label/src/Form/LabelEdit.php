<?php


namespace MakinaCorpus\Ucms\Label\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Label\LabelAccess;
use MakinaCorpus\Ucms\Label\LabelManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Label creation and edition form
 */
class LabelEdit extends FormBase
{

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_label.manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var LabelManager
     */
    protected $manager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(LabelManager $manager, EventDispatcherInterface $dispatcher)
    {
        $this->manager = $manager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_label_edit';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $term = null)
    {
        $form['#form_horizontal'] = true;

        if ($term === null) {
            $term = new \stdClass();
        }

        $form_state->setTemporaryValue('term', $term);

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => t('Name'),
            '#default_value' => isset($term->name) ? $term->name : '',
            '#maxlength' => 255,
            '#required' => true,
            '#weight' => -5,
        );

//        $form['description'] = array(
//            '#type' => 'text_format',
//            '#title' => t('Description'),
//            '#default_value' => $term->description,
//            '#format' => $term->format,
//            '#weight' => 0,
//        );

        // taxonomy_get_tree and taxonomy_get_parents may contain large numbers of
        // items so we check for taxonomy_override_selector before loading the
        // full vocabulary. Contrib modules can then intercept before
        // hook_form_alter to provide scalable alternatives.
        if (!variable_get('taxonomy_override_selector', FALSE)) {
            $has_children = false;

            if (isset($term->tid)) {
                $parent = $this->manager->loadParent($term);
                $has_children = $this->manager->hasChildren($term);
            }

            $root_labels = $this->manager->loadRootLabels();

            $options = [];
            foreach ($root_labels as $label) {
                if (!isset($term->tid) || $term->tid != $label->tid) {
                    $options[$label->tid] = $label->name;
                }
            }

            $form['parent'] = array(
                '#type' => 'select',
                '#title' => t('Parent term'),
                '#options' => $options,
                '#empty_value' => '0',
                '#empty_option' => '<' . t('root') . '>',
                '#default_value' => !empty($parent) ? $parent->tid : null,
                '#multiple' => false,
            );

            if ($has_children) {
                $form['parent']['#disabled'] = true;
                $form['parent']['#description'] = t("You must move or delete the children labels if you want to define a parent label for this one.");
            }
        }

        $form['locked'] = array(
            '#type' => 'checkbox',
            '#title' => t('Non editable label'),
            '#default_value' => isset($term->is_locked) ? $term->is_locked : 0,
        );

        if (!isset($term->tid) && !$this->manager->canEditAllLabels()) {
            $form['locked']['#disabled'] = true;
            if (!$this->manager->canEditLockedLabels()) {
                $form['locked']['#default_value'] = 1;
            }
        }

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Save'),
            '#weight' => 5,
        );

//        if ($term !== null) {
//            $form['actions']['delete'] = array(
//                '#type' => 'submit',
//                '#value' => t('Delete'),
//                '#weight' => 10,
//            );
//        }

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $term = $form_state->getTemporaryValue('term');
        $term->name = $form_state->getValue('name');
        $term->is_locked = $form_state->getValue('locked');
        $term->parent = ($parent = $form_state->getValue('parent')) ? $parent : 0;
        $term->vid = $this->manager->getVocabularyId();
        $term->vocabulary_machine_name = $this->manager->getVocabularyMachineName();
        $this->manager->saveLabel($term);

        drupal_set_message($this->t("The new label has been created."));

        //$this->dispatcher->dispatch('label:request', new ResourceEvent('label', $term->tid, $this->currentUser()->uid));

        $form_state->setRedirect('admin/dashboard/label');
    }

}
