<?php

namespace MakinaCorpus\Ucms\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Layout\Context;
use MakinaCorpus\Ucms\Layout\ContextManager;
use MakinaCorpus\Ucms\Layout\Layout;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LayoutContextEditForm extends FormBase
{
    /**
     * @var ContextManager
     */
    private $manager;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_layout.context_manager'));
    }

    /**
     * Default constructor
     *
     * @param Context $context
     */
    public function __construct(ContextManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_layout_context_edit_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $pageLayout = $this->manager->getPageContext()->getCurrentLayout();
        $transversalLayout = $this->manager->getTransversalContext()->getCurrentLayout();

        if ($pageLayout instanceof Layout || $transversalLayout instanceof Layout) {
            $form['actions']['#type'] = 'actions';

            if ($this->manager->getPageContext()->isTemporary()) {
                $form['actions']['save_page'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Save"),
                    '#submit' => ['::saveSubmit']
                ];
                $form['actions']['cancel_page'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Cancel"),
                    '#submit' => ['::cancelSubmit']
                ];
            }
            elseif ($this->manager->getTransversalContext()->isTemporary()) {
                $form['actions']['save_site'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Save"),
                    '#submit' => ['::saveTransversalSubmit']
                ];
                $form['actions']['cancel_site'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Cancel"),
                    '#submit' => ['::cancelTransversalSubmit']
                ];
            }
            else {
                $form['actions']['edit_page'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Edit"),
                    '#submit' => ['::editSubmit']
                ];
                $form['actions']['edit_site'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Edit transversal regions"),
                    '#submit' => ['::editTransversalSubmit']
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
        if ($this->manager->getPageContext()->isTemporary()) {
            $this->manager->getPageContext()->commit();

            drupal_set_message($this->t("Changed have been saved"));

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
        if ($this->manager->getPageContext()->isTemporary()) {
            $this->manager->getPageContext()->rollback();

            drupal_set_message($this->t("Changes have been dropped"), 'error');

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
        if (!$this->manager->getPageContext()->isTemporary()) {

            // @todo Generate a better token (random).
            $token  = drupal_get_token();
            $layout = $this->manager->getPageContext()->getCurrentLayout();
            $this->manager->getPageContext()->setToken($token);

            // Saving the layout will force it be saved in the temporary storage.
            $this->manager->getPageContext()->getStorage()->save($layout);

            $form_state->setRedirect(
                current_path(),
                ['query' => ['edit' => $token] + drupal_get_query_parameters()]
            );
        }
    }

    /**
     * Save form submit
     */
    public function saveTransversalSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->manager->getTransversalContext()->isTemporary()) {
            $this->manager->getTransversalContext()->commit();

            drupal_set_message(t("Changed have been saved"));

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', 'site_edit'])]
            );
        }
    }

    /**
     * Cancel form submit
     */
    public function cancelTransversalSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->manager->getTransversalContext()->isTemporary()) {
            $this->manager->getTransversalContext()->rollback();

            drupal_set_message(t("Changes have been dropped"), 'error');

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', 'site_edit'])]
            );
        }
    }

    /**
     * Edit form submit
     */
    public function editTransversalSubmit(array &$form, FormStateInterface $form_state)
    {
        if (!$this->manager->getTransversalContext()->isTemporary()) {

            // @todo Generate a better token (random).
            $token  = drupal_get_token();
            $layout = $this->manager->getTransversalContext()->getCurrentLayout();
            $this->manager->getTransversalContext()->setToken($token);

            // Saving the layout will force it be saved in the temporary storage.
            $this->manager->getTransversalContext()->getStorage()->save($layout);

            $form_state->setRedirect(
                current_path(),
                ['query' => ['site_edit' => $token] + drupal_get_query_parameters()]
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
