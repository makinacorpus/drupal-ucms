<?php


namespace MakinaCorpus\Ucms\Label\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;
use MakinaCorpus\Ucms\Notification\NotificationService;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Subscription to the labels notifications
 */
class LabelSubscribe extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_notification.service'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var NotificationService
     */
    protected $notifService;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    /**
     * Constructor
     *
     * @param NotificationService $notifService
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(NotificationService $notifService, EventDispatcherInterface $dispatcher)
    {
        $this->notifService = $notifService;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_label_subscribe';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, \stdClass $label = null)
    {
        if ($label === null) {
            return [];
        }

        $form_state->setTemporaryValue('label', $label);
        $question = $this->t("Subscribe to the %name label notifications?", ['%name' => $label->name]);
        return confirm_form($form, $question, 'admin/dashboard/label', '');
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $label = $form_state->getTemporaryValue('label');
        $this->notifService->subscribe($this->currentUser()->id(), 'label:' . $label->tid);
        drupal_set_message($this->t("You subscribed to the %name label notifications.", array('%name' => $label->name)));
    }
}
