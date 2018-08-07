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
 * User enabling form
 */
class UserEnable extends FormBase
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
        return 'ucms_user_enable';
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

        if ($user->isBlocked() && ((int) $user->getLastAccessedTime() === 0)) {
            $form['explanation'] = [
                '#type' => 'item',
                '#markup' => $this->t("This user has never been connected. You have to define a password and pass it on to the user by yourself."),
            ];

            $form['password'] = [
                '#type' => 'password_confirm',
                '#size' => 20,
                '#required' => true,
                '#description' => $this->t("!count characters at least. Mix letters, digits and special characters for a better password.", ['!count' => UCMS_USER_PWD_MIN_LENGTH]),
            ];

            $form['actions'] = ['#type' => 'actions'];
            $form['actions']['submit'] = [
                '#type' => 'submit',
                '#value' => $this->t('Enable'),
            ];

            return $form;
        }
        else {
            $question = $this->t("Do you really want to enable the user @name?", ['@name' => $user->name]);
            return confirm_form($form, $question, 'admin/dashboard/user', '');
        }
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        /* @var $user UserInterface */
        $user = $form_state->getTemporaryValue('user');

        if (
            $user->isBlocked() &&
            ((int) $user->getLastAccessedTime() === 0) &&
            (strlen($form_state->getValue('password')) < UCMS_USER_PWD_MIN_LENGTH)
        ) {
            $form_state->setErrorByName('password', $this->t("The password must contain !count characters at least.",  ['!count' => UCMS_USER_PWD_MIN_LENGTH]));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /* @var $user UserInterface */
        $user = $form_state->getTemporaryValue('user');
        $user->status = 1;

        if ($user->isBlocked() && ((int) $user->getLastAccessedTime() === 0)) {
            require_once DRUPAL_ROOT . '/includes/password.inc';
            $user->pass = user_hash_password($form_state->getValue('password'));
        }

        if ($this->entityManager->getStorage('user')->save($user)) {
            drupal_set_message($this->t("User @name has been enabled.", array('@name' => $user->getDisplayName())));
            $this->dispatcher->dispatch('user:enable', new UserEvent($user->id(), $this->currentUser()->id()));
        } else {
            drupal_set_message($this->t("An error occured. Please try again."), 'error');
        }

        $form_state->setRedirect('admin/dashboard/user');
    }

}
