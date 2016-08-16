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

use MakinaCorpus\Ucms\User\EventDispatcher\UserEvent;
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
        $relativeRoles = $this->siteManager->getAccess()->collectRelativeRoles($site);
        $rolesAssociations = $this->siteManager->getAccess()->getRolesAssociations();

        foreach ($rolesAssociations as $rid => $rrid) {
            if (isset($relativeRoles[$rrid])) {
                $roles[$rid] = $this->siteManager->getAccess()->getDrupalRoleName($rid);
            }
        }

        $form['role'] = [
            '#type' => 'radios',
            '#title' => $this->t('Roles'),
            '#options' => $roles,
            '#required' => true,
        ];

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
                '#description' => $this->t("!count characters at least. Mix letters, digits and special characters for a better password.", ['!count' => 8]),
            ],
        ];

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Create'),
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

        if ((int) $form_state->getValue('enable') === 1) {
            if (strlen($form_state->getValue('password')) === 0) {
                $form_state->setErrorByName('password', $this->t("You must define a password to enable the user."));
            }
            elseif (strlen($form_state->getValue('password')) < 8) {
                $form_state->setErrorByName('password', $this->t("The password must contain !count characters at least.",  ['!count' => 8]));
            }
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

        require_once DRUPAL_ROOT . '/includes/password.inc';

        if ((int) $form_state->getValue('enable') === 1) {
            $user->pass = user_hash_password($form_state->getValue('password'));
            $user->status = 1;
        } else {
            $user->pass = user_hash_password(user_password(20));
            $user->status = 0; // Ensures the user is disabled by default
        }

        $this->entityManager->getStorage('user')->save($user);

        $site = $form_state->getTemporaryValue('site');
        $rid = $form_state->getValue('role');
        $rolesAssociations = $this->siteManager->getAccess()->getRolesAssociations();

        $this->siteManager->getAccess()->mergeUsersWithRole($site, $user->id(), $rolesAssociations[$rid]);

        drupal_set_message($this->t("!name has been created and added as %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->siteManager->getAccess()->getDrupalRoleName($rid),
        ]));

        $event = new SiteEvent($site, $this->currentUser()->id(), ['webmaster_id' => $user->id()]);
        $this->dispatcher->dispatch('site:webmaster_add_new', $event);

        $this->dispatcher->dispatch('user:add', new UserEvent($user->id(), $this->currentUser()->uid));
    }
}

