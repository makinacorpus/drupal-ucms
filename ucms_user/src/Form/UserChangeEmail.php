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
class UserChangeEmail extends FormBase
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
        return 'ucms_user_change_email';
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

        $form['#form_horizontal'] = true;

        $form['mail'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email'),
            '#default_value' => $user->getEmail(),
            '#maxlength' => 254,
            '#required' => true,
        );

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        );

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
                ->condition('uid', $user->id(), '<>')
                ->range(0, 1);

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
        /* @var $user UserInterface */
        $user = $form_state->getTemporaryValue('user');
        $user->setEmail($form_state->getValue('mail'));

        $saved = $this->entityManager->getStorage('user')->save($user);

        if ($saved) {
            drupal_set_message($this->t("@name's email address has been changed.", array('@name' => $user->name)));
            $this->dispatcher->dispatch('user:change_email', new UserEvent($user->uid, $this->currentUser()->id()));
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}
