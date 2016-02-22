<?php


namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\User\EventDispatcher\UserEvent;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * User deletion form
 */
class UserDelete extends FormBase
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
        return 'ucms_user_delete';
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
        $question = $this->t("Do you really want to delete the user @name?", ['@name' => $user->name]);

        return confirm_form($form, $question, 'admin/dashboard/user');
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getTemporaryValue('user');
        user_delete($user->uid);

        drupal_set_message($this->t("User @name has been deleted.", array('@name' => $user->name)));
        //$this->dispatcher->dispatch('user:delete', new UserEvent($user->uid, $this->currentUser()->uid));

        $form_state->setRedirect('admin/dashboard/user');
    }

}

