<?php


namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\EventDispatcher\UserEvent;
use MakinaCorpus\Ucms\User\TokenManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * User creation and edition form
 */
class UserEdit extends FormBase
{

    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity.manager'),
            $container->get('ucms_site.manager'),
            $container->get('ucms_user.token_manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var SiteManager
     */
    protected $siteManager;

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
        SiteManager $siteManager,
        TokenManager $tokenManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
        $this->tokenManager = $tokenManager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_user_edit';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = null)
    {
        $form['#form_horizontal'] = true;

        if ($user === null) {
            $user = $this->entityManager->getStorage('user')->create();
        }

        $form_state->setTemporaryValue('user', $user);

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Lastname / Firstname'),
            '#default_value' => !$user->isNew() ? $user->getAccountName() : '',
            '#maxlength' => 60,
            '#required' => true,
            '#weight' => -10,
        );

        $form['mail'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email'),
            '#default_value' => $user->getEmail(),
            '#maxlength' => EMAIL_MAX_LENGTH,
            '#required' => true,
            '#weight' => -5,
        );

        if ($user->id() === $this->currentUser()->id()) {
            $form['mail']['#disabled'] = true;
            $form['mail']['#description'] = $this->t("Please use this form to edit your e-mail: !link", [
                '!link' => l($this->t("change my e-mail"), 'admin/dashboard/user/my-account'),
            ]);
        }

        $allRoles = $this->siteManager->getAccess()->getDrupalRoleList();
        unset($allRoles[DRUPAL_ANONYMOUS_RID]);
        unset($allRoles[DRUPAL_AUTHENTICATED_RID]);
        $siteRoles = $this->siteManager->getAccess()->getRelativeRoles();
        $availableRoles = array_diff_key($allRoles, $siteRoles);

        $form['roles'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Roles'),
            '#options' => $availableRoles,
            '#default_value' => $user->getRoles(),
        ];

        if ($user->isNew()) {
            $form['enable'] = array(
                '#type' => 'checkbox',
                '#title' => $this->t('Enable the user'),
                '#default_value' => 0,
                '#description' => $this->t("You will have to define a password and pass it on to the user by yourself."),
            );

            $form['password_container'] = [
                // Yes, a container... because password_confirm elements seem to not support #states property.
                '#type' => 'container',
                '#states' => [
                    'visible' => [':input[name="enable"]' => ['checked' => true]],
                    'enabled' => [':input[name="enable"]' => ['checked' => true]], // This one to avoid non matching values at submit...
                ],
                'password' => [
                    '#type' => 'password_confirm',
                    '#size' => 20,
                    '#description' => $this->t("!count characters at least. Mix letters, digits and special characters for a better password.", ['!count' => UCMS_USER_PWD_MIN_LENGTH]),
                ],
            ];
        }

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $user->isNew() ? $this->t('Create') : $this->t('Save'),
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        // Trim whitespace from mail, to prevent confusing 'e-mail not valid'
        // warnings often caused by cutting and pasting.
        $mail = $form_state->getValue('mail');
        $mail = trim($mail);
        $form_state->setValue('mail', $mail);

        // Validate the e-mail address, and check if it is taken by an existing user.
        if ($error = user_validate_mail($mail)) {
            $form_state->setErrorByName('mail', $error);
        }
        else {
            /* @var UserInterface $user */
            $user = $form_state->getTemporaryValue('user');

            $q = db_select('users')
                ->fields('users', array('uid'))
                ->condition('mail', db_like($mail), 'LIKE')
                ->range(0, 1);

            if (!$user->isNew()) {
                $q->condition('uid', $user->id(), '<>');
            }

            if ((bool) $q->execute()->fetchField()) {
                form_set_error('mail', $this->t('The e-mail address %email is already taken.', array('%email' => $mail)));
            }

            // Validate username must be unique
            $userName = $form_state->getValue('name');
            $q = db_select('users')->fields('users', ['uid'])->condition('name', db_like($userName), 'LIKE');
            if (!$user->isNew()) {
                $q->condition('uid', $user->id(), '<>');
            }
            $exists = $q->range(0, 1)->execute()->fetchField();
            if ($exists) {
                $form_state->setErrorByName('name', $this->t("A user with the same user name already exists."));
            }
        }

        if ((int) $form_state->getValue('enable') === 1) {
            if (strlen($form_state->getValue('password')) === 0) {
                $form_state->setErrorByName('password', $this->t("You must define a password to enable the user."));
            }
            elseif (strlen($form_state->getValue('password')) < UCMS_USER_PWD_MIN_LENGTH) {
                $form_state->setErrorByName('password', $this->t("The password must contain !count characters at least.",  ['!count' => UCMS_USER_PWD_MIN_LENGTH]));
            }
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getTemporaryValue('user');
        $is_new = $user->isNew(); // Stores this information to use it after save.

        $user->setUsername($form_state->getValue('name'));
        $user->setEmail($form_state->getValue('mail'));

        // New user
        if ($is_new) {
            require_once DRUPAL_ROOT . '/includes/password.inc';

            if ((int) $form_state->getValue('enable') === 1) {
                $user->pass = user_hash_password($form_state->getValue('password'));
                $user->status = 1;
            } else {
                $user->pass = user_hash_password(user_password(20));
                $user->status = 0;
            }
        }

        // Prepares user roles
        $userRoles  = array_filter($form_state->getValue('roles', []));
        $siteRoles  = $this->siteManager->getAccess()->getRelativeRoles();

        foreach (array_keys($siteRoles) as $rid) {
            if (isset($user->roles[$rid])) {
                $userRoles[$rid] = true;
            }
        }

        $user->roles = $userRoles;

        // Saves the user
        if ($this->entityManager->getStorage('user')->save($user)) {
            if ($is_new) {
                drupal_set_message($this->t("The new user @name has been created.", array('@name' => $user->name)));

                if ($user->isActive()) {
                    $this->tokenManager->sendTokenMail($user, 'new-account-enabled');
                } else {
                    $this->tokenManager->sendTokenMail($user, 'new-account-disabled');
                }

                $this->dispatcher->dispatch('user:add', new UserEvent($user->uid, $this->currentUser()->uid));
            } else {
                drupal_set_message($this->t("The user @name has been updated.", array('@name' => $user->name)));
                $this->dispatcher->dispatch('user:edit', new UserEvent($user->uid, $this->currentUser()->uid));
            }
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}
