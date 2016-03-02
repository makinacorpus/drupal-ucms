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
 * User password form
 */
class UserSetPassword extends FormBase
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
        return 'ucms_user_set_password';
    }


    /**
     * {@inheritdoc}
     * @param string $token
     */
    public function buildForm(array $form, FormStateInterface $form_state, $token = null)
    {
        if ($token === null) {
            return [];
        }

        /* @var Token $token */
        $token = $this->tokenManager->loadToken($token);
        if ($token === null) {
            drupal_set_message(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
            return [];
        }

        $currentUser = $this->currentUser();
        $userStorage = $this->entityManager->getStorage('user');

        if ($currentUser->id()) {
            // The existing user is already logged in.
            if ($currentUser->id() == $token->uid) {
                drupal_set_message($this->t('You are logged in as %user. <a href="!user_edit">Change your password.</a>', [
                    '%user' => $currentUser->getAccountName(),
                    '!user_edit' => url('user/password'),
                ]));
            }
            // A different user is already logged in on the computer.
            else {
                /* @var UserInterface $user */
                $tokenUser = $userStorage->load($token->uid);

                drupal_set_message($this->t('Another user (%other_user) is already logged into the site on this computer, but you tried to use a one-time link for user %resetting_user. Please <a href="!logout">logout</a> and try using the link again.', [
                    '%other_user' => $currentUser->getAccountName(),
                    '%resetting_user' => $tokenUser->getAccountName(),
                    '!logout' => url('user/logout'),
                ]), 'warning');
            }

            drupal_goto();
        }
        else {
            /* @var UserInterface $user */
            $user = $userStorage->load($token->uid);

            if ($user->isBlocked() && $user->getLastLoginTime() > 0) {
                drupal_access_denied();
                drupal_exit();
            }

            if ($token->isValid()) {
                $form_state->setTemporaryValue('token', $token);
                $form_state->setTemporaryValue('user', $user);

                $form['#form_horizontal'] = true;

                $form['password'] = [
                    '#type' => 'password_confirm',
                    '#size' => 20,
                    '#required' => true,
                ];

                $form['actions'] = [
                    '#type' => 'actions',
                    '#weight' => 100,
                    'submit' => [
                        '#type' => 'submit',
                        '#value' => $this->t('Save my password'),
                    ],
                ];
            }
            else {
                drupal_set_message(t('You have tried to use a one-time login link that has either been used or is no longer valid. Please request a new one using the form below.'), 'error');
                drupal_goto('user/password');
            }
        }

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        require_once DRUPAL_ROOT . '/includes/password.inc';

        /* @var Token $token */
        $token = $form_state->getTemporaryValue('token');
        /* @var UserInterface $user */
        $user = $form_state->getTemporaryValue('user');

        $user->pass = user_hash_password($form_state->getValue('password'));

        if ($user->getLastLoginTime() == 0) {
            $user->status = 1;
        }

        $saved = $this->entityManager->getStorage('user')->save($user);

        if ($saved) {
            $this->tokenManager->deleteToken($token);
            drupal_set_message($this->t("Your password has been recorded."));
            $this->dispatcher->dispatch('user:set_password', new UserEvent($user->uid, $this->currentUser()->id()));
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('user/login');
    }

}


