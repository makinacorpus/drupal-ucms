<?php


namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\APubSub\Notification\EventDispatcher\ResourceEvent;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * User password reset form
 */
class UserResetPassword extends FormBase
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
        return 'ucms_user_reset_password';
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
        $question = $this->t("Do you really want to reset the password of the user @name?", ['@name' => $user->name]);

        return confirm_form($form, $question, 'admin/dashboard/user');
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            require_once DRUPAL_ROOT . '/includes/password.inc';
            
            $user = $form_state->getTemporaryValue('user');
            $user->pass = user_hash_password(user_password(20));

            if (user_save($user)) {
                drupal_set_message($this->t("@name's password has been resetted.", array('@name' => $user->name)));
                //$this->dispatcher->dispatch('user:reset_password', new ResourceEvent('user', $user->uid, $this->currentUser()->uid));
            } else {
                throw new \RuntimeException('Call to user_save() failed!');
            }
        }
        catch (\Exception $e) {
            drupal_set_message($this->t("An error occured during resetting the password of the user @name. Please try again.", array('@name' => $user->name)), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}


