<?php


namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

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
            $container->get('entity.manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $entityManager;
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
    public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = null)
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
        /* @var $user UserInterface */
        $user = $form_state->getTemporaryValue('user');
        if ($user->isActive()) {
            $user->status = 0;
        } else {
            $user->status = 1;
        }

        if (user_save($user)) {
            if ($user->status) {
                drupal_set_message($this->t("User @name has been enabled.", array('@name' => $user->getDisplayName())));
                $this->dispatcher->dispatch('user:enable', new UserEvent($user->id(), $this->currentUser()->id()));
            } else {
                drupal_set_message($this->t("User @name has been disabled.", array('@name' => $user->getDisplayName())));
                $this->dispatcher->dispatch('user:disable', new UserEvent($user->id(), $this->currentUser()->id()));
            }
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}
