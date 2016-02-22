<?php


namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\User\EventDispatcher\UserEvent;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * User creation and edition form
 */
class UserToggleStatus extends FormBase
{

    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_user_toggle_status';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, \stdClass $user = null)
    {
        if ($user === null) {
            return [];
        }

        $form_state->setTemporaryValue('user', $user);

        if ($user->status) {
            $question = $this->t("Do you really want to disable the user @name?", ['@name' => $user->name]);
        } else {
            $question = $this->t("Do you really want to enable the user @name?", ['@name' => $user->name]);
        }

        return confirm_form($form, $question, 'admin/dashboard/user', '');
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getTemporaryValue('user');
        $user->status = $user->status ? 0 : 1;

        if (user_save($user)) {
            if ($user->status) {
                drupal_set_message($this->t("User @name has been enabled.", array('@name' => $user->name)));
                $this->dispatcher->dispatch('user:enable', new UserEvent($user->uid, $this->currentUser()->uid));
            } else {
                drupal_set_message($this->t("User @name has been disabled.", array('@name' => $user->name)));
                $this->dispatcher->dispatch('user:disable', new UserEvent($user->uid, $this->currentUser()->uid));
            }
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}
