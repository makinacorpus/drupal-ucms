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

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
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
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getTemporaryValue('user');
        $is_new = empty($user->uid);

        // New user
        if ($is_new) {
            // Sets a password
            require_once DRUPAL_ROOT . '/includes/password.inc';
            $user->pass = user_hash_password(user_password(20));
            // Ensure the user is disabled
            $user->status = 0;
        }

        // Prepares user roles
        $userRoles  = $form_state->getValue('roles', []);
        $siteRoles  = $this->siteManager->getAccess()->getRelativeRoles();

        foreach (array_keys($siteRoles) as $rid) {
            if (isset($user->roles[$rid])) {
                $userRoles[$rid] = true;
            }
        }

        $form_state->setValue('roles', $userRoles);

        // Saves the user
        if (user_save($user, $form_state->getValues())) {
            if ($is_new) {
                drupal_set_message($this->t("The new user @name has been created.", array('@name' => $user->name)));
                $this->tokenManager->sendTokenMail($user, 'new-account');
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
