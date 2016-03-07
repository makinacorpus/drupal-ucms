<?php

namespace MakinaCorpus\Ucms\User\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to change its own password.
 */
class MyAccountChangePassword extends FormBase
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
        return 'ucms_user_my_account_change_password';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $account = $this->entityManager->getStorage('user')->load($this->currentUser()->id());
        $form_state->setTemporaryValue('account', $account);

        $form['#form_horizontal'] = true;

        $form['current_password'] = [
            '#type' => 'password',
            '#title' => $this->t('Current password'),
            '#size' => 20,
            '#required' => true,
        ];

        $form['new_password'] = [
            '#type' => 'password_confirm',
            //'#title' => $this->t('New password'),
            '#size' => 20,
            '#required' => true,
        ];

        $form['actions'] = [
            '#type' => 'actions',
            'submit' => [
                '#type' => 'submit',
                '#value' => $this->t('Save'),
            ],
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        require_once DRUPAL_ROOT . '/' . variable_get('password_inc', 'includes/password.inc');

        $account = $form_state->getTemporaryValue('account');
        $password = $form_state->getValue('current_password');

        if (!user_check_password($password, $account)) {
            $form_state->setErrorByName('current_password', $this->t("Your current password is incorrect."));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $new_password = $form_state->getValue('new_password');

        $account = $form_state->getTemporaryValue('account');
        $account->pass = user_hash_password($new_password);
        $this->entityManager->getStorage('user')->save($account);

        drupal_set_message($this->t("Your new password has been saved."));
        $form_state->setRedirect('admin/dashboard');
    }
}

