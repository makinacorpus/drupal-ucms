<?php


namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\User\EventDispatcher\UserEvent;
use MakinaCorpus\Ucms\User\TokenManager;

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
            $container->get('entity.manager'),
            $container->get('ucms_user.token_manager'),
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

    /**
     * @var TokenManager
     */
    protected $tokenManager;


    public function __construct(
        EntityManager $entityManager,
        TokenManager $tokenManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
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
    public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = null)
    {
        if ($user === null) {
            return [];
        }

        $form_state->setTemporaryValue('user', $user);
        $question = $this->t("Do you really want to reset the password of the user @name?", ['@name' => $user->getDisplayName()]);

        return confirm_form($form, $question, 'admin/dashboard/user');
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        require_once DRUPAL_ROOT . '/includes/password.inc';

        /* @var $user UserInterface */
        $user = $form_state->getTemporaryValue('user');
        $user->pass = user_hash_password(user_password(20));

        $saved = $this->entityManager->getStorage('user')->save($user);

        if ($saved) {
            drupal_set_message($this->t("@name's password has been resetted.", array('@name' => $user->getDisplayName())));
            $this->tokenManager->sendTokenMail($user, 'ucms_user', 'password-reset');
            $this->dispatcher->dispatch('user:reset_password', new UserEvent($user->uid, $this->currentUser()->id()));
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}


