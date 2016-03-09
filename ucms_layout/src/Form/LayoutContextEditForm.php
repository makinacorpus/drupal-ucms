<?php

namespace MakinaCorpus\Ucms\Layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Layout\Context;
use MakinaCorpus\Ucms\Layout\Layout;

use Symfony\Component\DependencyInjection\ContainerInterface;

class LayoutContextEditForm extends FormBase
{
    /**
     * @var Context
     */
    private $pageContext;

    /**
     * @var Context
     */
    private $siteContext;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_layout.page_context'),
            $container->get('ucms_layout.site_context')
        );
    }

    /**
     * Default constructor
     *
     * @param Context $context
     */
    public function __construct(Context $pageContext, Context $siteContext)
    {
        $this->pageContext = $pageContext;
        $this->siteContext = $siteContext;
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
        $pageLayout = $this->pageContext->getCurrentLayout();
        $siteLayout = $this->siteContext->getCurrentLayout();

        if ($pageLayout instanceof Layout || $siteLayout instanceof Layout) {
            $form['actions']['#type'] = 'actions';

            if ($this->pageContext->isTemporary()) {
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
            elseif ($this->siteContext->isTemporary()) {
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
        if ($this->pageContext->isTemporary()) {
            $this->pageContext->commit();

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
        if ($this->pageContext->isTemporary()) {
            $this->pageContext->rollback();

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
        if (!$this->pageContext->isTemporary()) {

            // @todo Generate a better token (random).
            $token  = drupal_get_token();
            $layout = $this->pageContext->getCurrentLayout();
            $this->pageContext->setToken($token);

            // Saving the layout will force it be saved in the temporary storage.
            $this->pageContext->getStorage()->save($layout);

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
        if ($this->siteContext->isTemporary()) {
            $this->siteContext->commit();

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
        if ($this->siteContext->isTemporary()) {
            $this->siteContext->rollback();

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
        if (!$this->siteContext->isTemporary()) {

            // @todo Generate a better token (random).
            $token  = drupal_get_token();
            $layout = $this->siteContext->getCurrentLayout();
            $this->siteContext->setToken($token);

            // Saving the layout will force it be saved in the temporary storage.
            $this->siteContext->getStorage()->save($layout);

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
