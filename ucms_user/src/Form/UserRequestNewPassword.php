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
 * New password request form
 */
class UserRequestNewPassword extends FormBase
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
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var TokenManager
     */
    protected $tokenManager;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


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
        return 'ucms_user_request_new_password';
    }


    /**
     * {@inheritdoc}
     * @param string $token
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['mail'] = [
            '#type' => 'textfield',
            '#title' => $this->t("E-mail address"),
            '#description' => $this->t("You must supply the same address as the one given in your account."),
            '#maxlength' => EMAIL_MAX_LENGTH,
            '#required' => true,
        ];

        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type' => 'submit',
                '#value' => $this->t('Send my request'),
            ],
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        // Trim whitespace from mail
        $mail = $form_state->getValue('mail');
        $mail = trim($mail);

        if (!valid_email_address($mail)) {
            $form_state->setErrorByName('mail', $this->t('The e-mail address you specified is not valid.'));
        }

        $uid = db_select('users')
            ->fields('users', array('uid'))
            ->condition('mail', $mail)
            ->condition('status', 1)
            ->range(0, 1)
            ->execute()
            ->fetchField();

        if ($uid) {
            $user = $this->entityManager->getStorage('user')->load($uid);
            $form_state->setTemporaryValue('user', $user);
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /* @var UserInterface $user */
        $user = $form_state->getTemporaryValue('user');
        if ($user) {
            $this->tokenManager->sendTokenMail($user, 'ucms_user', 'new-password-request');
            $event = new UserEvent($user->uid, $this->currentUser()->id());
            $this->dispatcher->dispatch('user:request_new_password', $event);
        }
        drupal_set_message($this->t("If this adress is known to us, you will receive further instructions."));
    }

}



