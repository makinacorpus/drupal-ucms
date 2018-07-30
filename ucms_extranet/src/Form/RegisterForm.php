<?php


namespace MakinaCorpus\Ucms\Extranet\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Extranet\ExtranetAccess;
use MakinaCorpus\Ucms\Extranet\EventDispatcher\ExtranetMemberEvent;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\User\TokenManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to assign a webmaster/contributor to a site.
 */
class RegisterForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('entity.manager'),
            $container->get('ucms_user.token_manager'),
            $container->get('event_dispatcher')
        );
    }


    /**
     * @var SiteManager
     */
    protected $siteManager;

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
        SiteManager $siteManager,
        EntityManager $entityManager,
        TokenManager $tokenManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_extranet_register_form';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState)
    {
        if (!$this->siteManager->hasContext()) {
          return [];
        }

        $formState->setTemporaryValue('site', $this->siteManager->getContext());

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Lastname / Firstname'),
            '#maxlength' => USERNAME_MAX_LENGTH,
            '#required' => true,
            '#weight' => -10,
        );

        $form['mail'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email'),
            '#maxlength' => EMAIL_MAX_LENGTH,
            '#required' => true,
            '#weight' => -5,
        );

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Register'),
        );

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $formState)
    {
        // Validate the name and check if it is already taken by an existing user.
        $name = $formState->getValue('name');
        $name = trim($name);
        $formState->setValue('name', $name);

        if (!$name) {
            $formState->setErrorByName('name', $this->t('You must enter your name.'));
        }
        elseif (drupal_strlen($name) > USERNAME_MAX_LENGTH) {
            $formState->setErrorByName('name', $this->t('Your name is too long: it must be %max characters at the most.', ['%max' => USERNAME_MAX_LENGTH]));
        }
        elseif ((bool) db_select('users')
            ->fields('users', ['uid'])
            ->condition('name', db_like($name), 'LIKE')
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ) {
            $formState->setErrorByName('name', $this->t('The name %name is already taken.', ['%name' => $name]));
        }

        // Trim whitespace from mail, to prevent confusing 'e-mail not valid'
        // warnings often caused by cutting and pasting.
        $mail = $formState->getValue('mail');
        $mail = trim($mail);
        $formState->setValue('mail', $mail);

        // Validate the e-mail address and check if it is already taken by an existing user.
        if ($error = user_validate_mail($mail)) {
            $formState->setErrorByName('mail', $error);
        }
        elseif ((bool) db_select('users')
            ->fields('users', ['uid'])
            ->condition('mail', db_like($mail), 'LIKE')
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ) {
            $formState->setErrorByName('mail', $this->t('The e-mail address %email is already taken.', ['%email' => $mail]));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /** @var \Drupal\user\UserInterface $user */
        $user = $this->entityManager->getStorage('user')->create();
        /** @var \MakinaCorpus\Ucms\Site\Site $site */
        $site = $formState->getTemporaryValue('site');

        $user->setUsername($formState->getValue('name'));
        $user->setEmail($formState->getValue('mail'));
        $user->status = 0; // Ensures the user is disabled by default

        require_once DRUPAL_ROOT . '/includes/password.inc';
        $user->pass = user_hash_password(user_password(20));

        // Records the user
        $this->entityManager->getStorage('user')->save($user);
        // Gives it the extranet member role
        $this->siteManager->getAccess()->mergeUsersWithRole($site, $user->id(), ExtranetAccess::ROLE_EXTRANET_MEMBER);

        // Sends an e-mail notification to the extranet webmasters
        $accessRecords = $this->siteManager->getAccess()->listWebmasters($site);
        foreach ($accessRecords as $record) {
            $webmaster = $this->entityManager->getStorage('user')->load($record->getUserId());
            $params = ['user' => $user, 'site' => $site];
            drupal_mail('ucms_extranet', 'new-member-registered', $webmaster->getEmail(), $GLOBALS['language'], $params);
        }

        // Dispatches an event
        $event = new ExtranetMemberEvent($user, $site);
        $this->dispatcher->dispatch(ExtranetMemberEvent::EVENT_REGISTER, $event);

        $formState->setRedirect('user/register/confirm');
    }
}


