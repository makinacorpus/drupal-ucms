<?php

namespace MakinaCorpus\Ucms\Dashboard\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Dashboard\Action\AbstractActionProcessor;
use MakinaCorpus\Ucms\Dashboard\TransactionHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ActionProcessForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_dashboard.transaction_handler'));
    }

    private $transactionHandler;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_dashboard_action_form';
    }

    public function __construct(TransactionHandler $transactionHandler)
    {
        $this->transactionHandler = $transactionHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, AbstractActionProcessor $processor = null, $item = null)
    {
        if (!$processor) {
            return $form;
        }
        if (!$item) {
            return $form;
        }

        $form_state->setTemporaryValue('processor', $processor);
        $form_state->setTemporaryValue('item', $item);

        return confirm_form(
            $form,
            $processor->getQuestion([$item], 1),
            '<front>',
            $processor->getDescription()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /** @var $processor AbstractActionProcessor */
        $processor = $form_state->getTemporaryValue('processor');
        $item = $form_state->getTemporaryValue('item');

        $message = $this
            ->transactionHandler
            ->run(function () use ($processor, $item) {
                return $processor->process($item);
            })
        ;

        // No redirect, the API is always supposed to give us a destination
        if ($message) {
            drupal_set_message($message);
        } else {
            drupal_set_message($this->t("Action was done"));
        }
    }
}
