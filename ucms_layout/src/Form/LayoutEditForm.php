<?php

namespace MakinaCorpus\Ucms\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Layout\Context;
use MakinaCorpus\Ucms\Layout\Layout;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LayoutEditForm extends FormBase
{
    /**
     * @var Context
     */
    private $context;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_layout.context'));
    }

    /**
     * Default constructor
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_layout_edit_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $layout = $this->context->getCurrentLayout();

        if ($layout instanceof Layout) {
            if ($this->context->isTemporary()) {

                $form['actions']['#type'] = 'actions';
                $form['actions']['save'] = [
                    '#type'   => 'submit',
                    '#value'  => t("Save"),
                    '#submit' => ['::saveSubmit']
                ];
                $form['actions']['cancel'] = [
                    '#type'   => 'submit',
                    '#value'  => t("Cancel"),
                    '#submit' => ['::cancelSubmit']
                ];

            } else {
                  $form['actions']['#type'] = 'actions';
                  $form['actions']['edit'] = [
                      '#type'   => 'submit',
                      '#value'  => t("Edit"),
                      '#submit' => ['::editSubmit']
                  ];
            }
        }

        return $form;
    }

    /**
     * Save form submit
     */
    public function saveSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->context->isTemporary()) {
            $this->context->commit();

            drupal_set_message(t("Changed have been saved"));

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', 'edit'])]
            );
        }
    }

    /**
     * Cancel form submit
     */
    public function cancelSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->context->isTemporary()) {
            $this->context->rollback();

            drupal_set_message(t("Changes have been dropped"), 'error');

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', 'edit'])]
            );
        }
    }

    /**
     * Edit form submit
     */
    public function editSubmit(array &$form, FormStateInterface $form_state)
    {
        if (!$this->context->isTemporary()) {

            // @todo Generate a better token (random).
            $token  = drupal_get_token();
            $layout = $this->context->getCurrentLayout();
            $this->context->setToken($token);

            // Saving the layout will force it be saved in the temporary storage.
            $this->context->getStorage()->save($layout);

            $form_state->setRedirect(
                current_path(),
                ['query' => ['edit' => $token] + drupal_get_query_parameters()]
            );
        }
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // Nothing to do.
    }
}
