<?php


namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;

use MakinaCorpus\Ucms\Site\Access;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to assign a webmaster/contributor to a site.
 */
class WebmasterAddNew extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('entity.manager'),
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
     * @var EventDispatcherInterface
     */
    protected $dispatcher;


    public function __construct(
        SiteManager $siteManager,
        EntityManager $entityManager,
        EventDispatcherInterface $dispatcher
    ) {
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }


    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_webmaster_add_new';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        $form['#form_horizontal'] = true;

        $form_state->setTemporaryValue('site', $site);

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Lastname / Firstname'),
            '#maxlength' => 60,
            '#required' => true,
            '#weight' => -10,
        );

        $form['mail'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Email'),
            '#maxlength' => 254,
            '#required' => true,
            '#weight' => -5,
        );

        $roles = [];
        $relativeRoles = $this->siteManager->getAccess()->getRelativeRoles();
        foreach (array_keys($relativeRoles) as $rid) {
            $roles[$rid] = $this->siteManager->getAccess()->getDrupalRoleName($rid);
        }

        $form['role'] = [
            '#type' => 'radios',
            '#title' => $this->t('Roles'),
            '#options' => $roles,
            '#required' => true,
        ];

        $form['password'] = [
            '#type' => 'password_confirm',
            //'#title' => $this->t('Password'),
            '#size' => 20,
            '#description' => $this->t("!count characters at least. Mix letters, digits and special characters for a better password.", ['!count' => 8]),
        ];

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Create'),
            '#weight' => 10,
        );
        $form['actions']['submit_enable'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Create & enable'),
            '#validate' => ['::validateFormWithEnabling'],
            '#submit' => ['::submitFormWithEnabling'],
//            '#states' => [
//                'enabled' => [
//                    ':input[name="password[pass1]"]' => ['filled' => true],
//                    ':input[name="password[pass2]"]' => ['filled' => true],
//                ],
//            ],
            '#weight' => 20,
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
        elseif ((bool) db_select('users')
            ->fields('users', array('uid'))
            ->condition('mail', db_like($mail), 'LIKE')
            ->range(0, 1)
            ->execute()
            ->fetchField()
        ) {
            form_set_error('mail', $this->t('The e-mail address %email is already taken.', array('%email' => $mail)));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function validateFormWithEnabling(array &$form, FormStateInterface $form_state)
    {
        $this->validateForm($form, $form_state);

        $password = $form_state->getValue('password');
        if (strlen($password) < 8) {
            $form_state->setErrorByName('password', $this->t("The password must contain !count characters at least.",  ['!count' => 8]));
        }
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /* @var \Drupal\user\UserInterface $user */
        $user = $this->entityManager->getStorage('user')->create();

        $user->setUsername($form_state->getValue('name'));
        $user->setEmail($form_state->getValue('mail'));

        // Sets a random password
        require_once DRUPAL_ROOT . '/includes/password.inc';
        $user->pass = user_hash_password(user_password(20));
        // Ensures the user is disabled by default
        $user->status = 0;

        $this->entityManager->getStorage('user')->save($user);
        $this->saveAccessRights($user, $form_state);

        drupal_set_message($this->t("!name has been created and added as %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->siteManager->getAccess()->getDrupalRoleName($form_state->getValue('role')),
        ]));

        $event = new SiteEvent($form_state->getTemporaryValue('site'), $this->currentUser()->id(), ['webmaster_id' => $user->id()]);
        $this->dispatcher->dispatch('site:webmaster_add_new', $event);
    }


    /**
     * {@inheritdoc}
     */
    public function submitFormWithEnabling(array &$form, FormStateInterface $form_state)
    {
        /* @var \Drupal\user\UserInterface $user */
        $user = $this->entityManager->getStorage('user')->create();

        $user->setUsername($form_state->getValue('name'));
        $user->setEmail($form_state->getValue('mail'));

        // Sets the password
        require_once DRUPAL_ROOT . '/includes/password.inc';
        $user->pass = user_hash_password($form_state->getValue('password'));
        // Enables the user
        $user->status = 1;

        $this->entityManager->getStorage('user')->save($user);
        $this->saveAccessRights($user, $form_state);

        drupal_set_message($this->t("!name has been created, enabled and added as %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->siteManager->getAccess()->getDrupalRoleName($form_state->getValue('role')),
        ]));

        $event = new SiteEvent($form_state->getTemporaryValue('site'), $this->currentUser()->id(), ['webmaster_id' => $user->id()]);
        $this->dispatcher->dispatch('site:webmaster_add_new', $event);
    }


    /**
     * Creates a new user and sets the common properties of all the operations.
     * @param UserInterface $user
     * @param FormStateInterface $form_state
     */
    protected function saveAccessRights(UserInterface $user, FormStateInterface $form_state)
    {
        $site = $form_state->getTemporaryValue('site');
        $rid = $form_state->getValue('role');
        $relativeRoles = $this->siteManager->getAccess()->getRelativeRoles();

        if ((int) $relativeRoles[$rid] === Access::ROLE_WEBMASTER) {
            $this->siteManager->getAccess()->addWebmasters($site, $user->id());
        } else {
            $this->siteManager->getAccess()->addContributors($site, $user->id());
        }
    }
}

