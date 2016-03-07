<?php

namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MyAccountEdit
 * @package MakinaCorpus\Ucms\User\Form
 */
class MyAccountEdit extends FormBase
{
    use StringTranslationTrait;

    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('entity.manager')
        );
    }

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_user_my_account_edit';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $account = $this->currentUser()->getAccount();
        $form_state->setTemporaryValue('account', $account);

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Username'),
            '#maxlength' => USERNAME_MAX_LENGTH,
            '#description' => $this->t(
                'Spaces are allowed; punctuation is not allowed except for periods, hyphens, apostrophes, and underscores.'
            ),
            '#required' => true,
            '#attributes' => array('class' => array('username')),
            '#default_value' => $account->name,
            '#access' => user_access('change own username', $account),
        );

        $form['mail'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('E-mail address'),
            '#maxlength' => EMAIL_MAX_LENGTH,
            '#attributes' => ['placeholder' => $account->mail],
        );

        $form['mail_confirmation'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('E-mail address confirmation'),
            '#maxlength' => EMAIL_MAX_LENGTH,
            '#description' => $this->t(
                'A valid e-mail address. All e-mails from the system will be sent to this address. The e-mail address is not made public and will only be used if you wish to receive a new password or wish to receive certain news or notifications by e-mail.'
            ),
            '#attributes' => ['placeholder' => 'Confirm the new e-mail address'],
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
        $account = $form_state->getTemporaryValue('account');

        $new_name = $form_state->getValue('name');
        if ($new_name !== $account->name) {
            // Validate changing username.
            if ($error = user_validate_name($new_name)) {
                $form_state->setErrorByName('name', $error);
            } elseif ((bool)db_select('users')
                ->fields('users', array('uid'))
                ->condition('uid', $account->uid, '<>')
                ->condition('name', db_like($new_name), 'LIKE')
                ->range(0, 1)
                ->execute()
                ->fetchField()
            ) {
                $form_state->setErrorByName(
                    'name',
                    $this->t('The name %name is already taken.', ['%name' => $new_name])
                );
            }
        }

        if ($new_mail = $form_state->getValue('mail')) {
            // Trim whitespace from mail, to prevent confusing 'e-mail not valid'
            // warnings often caused by cutting and pasting.
            $new_mail = trim($new_mail);
            $form_state->setValue('mail', $new_mail);

            $mail_confirmation = trim($form_state->getValue('mail_confirmation'));

            // Validate the e-mail address, and check if it is taken by an existing user.
            if (!valid_email_address($new_mail)) {
                form_set_error(
                    'mail',
                    $this->t('The e-mail address %mail is not valid.', array('%mail' => $new_mail))
                );
            } elseif ($new_mail !== $mail_confirmation) {
                form_set_error(
                    'mail_confirmation',
                    $this->t("The e-mail's confirmation doesn't match with the first supplied e-mail.")
                );
            } elseif ((bool)db_select('users')
                ->fields('users', array('uid'))
                ->condition('uid', $account->uid, '<>')
                ->condition('mail', db_like($new_mail), 'LIKE')
                ->range(0, 1)
                ->execute()
                ->fetchField()
            ) {
                form_set_error(
                    'mail',
                    $this->t('The e-mail address %email is already taken.', array('%email' => $new_mail))
                );
            }
        }

    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $account = $form_state->getTemporaryValue('account');

        $account->setUsername($form_state->getValue('name'));
        if ($new_mail = $form_state->getValue('mail')) {
            $account->setEmail($new_mail);
        }

        $this->entityManager->getStorage('user')->save($account);

        // Clear the page cache because pages can contain usernames and/or profile information:
        cache_clear_all();
        drupal_set_message($this->t("Your account information has been updated."));
        $form_state->setRedirect('admin/dashboard');
    }
}
