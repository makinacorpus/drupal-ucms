<?php

namespace MakinaCorpus\Ucms\Dashboard\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Action\Impl\ProcessAction;

class ProcessActionConfirmForm extends ConfirmFormBase
{
    protected $action;

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, ProcessAction $action = null)
    {
        if (!$action || !$action->isGranted()) {
            return $form;
        }

        $this->action = $action;

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_dashboard_action_form';
    }

    /**
     * {@inheritdoc}
     */
    public function getQuestion()
    {
        if ($title = $this->action->getTitle()) {
            return $title;
        }

        return new TranslatableMarkup("Do it?");
    }

    public function getDescription()
    {
        if ($description = $this->action->getDescription()) {
            return $description;
        }

        return parent::getDescription();
    }

    /**
     * {@inheritdoc}
     */
    public function getCancelUrl()
    {
        // @todo There always should be a destination parameter
        return new Url('ucms_site.admin.site_list');
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        $message = $this->action->process();
        if (\is_string($message) || $message instanceof MarkupInterface) {
            \drupal_set_message($message);
        } else {
            \drupal_set_message(new TranslatableMarkup("Action done: @action", ['@action' => $this->action->getTitle()]));
        }

        // @todo should be driven by action
        $formState->setRedirect('ucms_site.admin.site_list');
    }
}
