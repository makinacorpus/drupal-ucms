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
        $siteLayout = $this->manager->getSiteContext()->getCurrentLayout();

        if ($pageLayout instanceof Layout || $siteLayout instanceof Layout) {
            $form['actions']['#type'] = 'actions';

            if ($this->manager->getPageContext()->isTemporary()) {
                $form['actions']['save_page'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Save composition"),
                    '#submit' => ['::saveSubmit']
                ];
                $form['actions']['cancel_page'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Cancel"),
                    '#submit' => ['::cancelSubmit']
                ];
            }
            elseif ($this->manager->getSiteContext()->isTemporary()) {
                $form['actions']['save_site'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Save transversal composition"),
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
                    '#value'  => $this->t("Edit composition"),
                    '#submit' => ['::editSubmit']
                ];
                $form['actions']['edit_site'] = [
                    '#type'   => 'submit',
                    '#value'  => $this->t("Edit transversal composition"),
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
                ['query' => drupal_get_query_parameters(null, ['q', ContextManager::PARAM_PAGE_TOKEN])]
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
                ['query' => drupal_get_query_parameters(null, ['q', ContextManager::PARAM_PAGE_TOKEN])]
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
                ['query' => [ContextManager::PARAM_PAGE_TOKEN => $token] + drupal_get_query_parameters()]
            );
        }
    }

    /**
     * Save form submit
     */
    public function saveTransversalSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->manager->getSiteContext()->isTemporary()) {
            $this->manager->getSiteContext()->commit();

            drupal_set_message(t("Changed have been saved"));

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', ContextManager::PARAM_SITE_TOKEN])]
            );
        }
    }

    /**
     * Cancel form submit
     */
    public function cancelTransversalSubmit(array &$form, FormStateInterface $form_state)
    {
        if ($this->manager->getSiteContext()->isTemporary()) {
            $this->manager->getSiteContext()->rollback();

            drupal_set_message(t("Changes have been dropped"), 'error');

            $form_state->setRedirect(
                current_path(),
                ['query' => drupal_get_query_parameters(null, ['q', ContextManager::PARAM_SITE_TOKEN])]
            );
        }
    }

    /**
     * Edit form submit
     */
    public function editTransversalSubmit(array &$form, FormStateInterface $form_state)
    {
        if (!$this->manager->getSiteContext()->isTemporary()) {

            // @todo Generate a better token (random).
            $token  = drupal_get_token();
            $layout = $this->manager->getSiteContext()->getCurrentLayout();
            $this->manager->getSiteContext()->setToken($token);

            // Saving the layout will force it be saved in the temporary storage.
            $this->manager->getSiteContext()->getStorage()->save($layout);

            $form_state->setRedirect(
                current_path(),
                ['query' => [ContextManager::PARAM_SITE_TOKEN => $token] + drupal_get_query_parameters()]
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
