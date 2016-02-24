<?php


namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
            '#title' => $this->t('Full name'),
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

        if ((boolean) variable_get('user_pictures', 0)) {
            $form['picture'] = array(
                '#type' => 'file_chunked',
                '#title' => $this->t('Picture'),
                '#upload_location' => file_build_uri(variable_get('user_picture_path', '')),
                '#description' => $this->t('The user picture. Pictures larger than @dimensions pixels will be scaled down.', array('@dimensions' => variable_get('user_picture_dimensions', '85x85'))) . ' ' . filter_xss_admin(variable_get('user_picture_guidelines', '')),
            );
        }

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

        $form['actions'] = array('#type' => 'actions');
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Create'),
            '#weight' => 100,
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
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /* @var \Drupal\user\User $user */
        $user = $this->entityManager->getStorage('user')->create();

        // Sets name and role
        $user->setUsername($form_state->getValue('name'));
        $user->setEmail($form_state->getValue('mail'));
        $user->addRole((int) $form_state->getValue('role'));
        // Sets a password
        require_once DRUPAL_ROOT . '/includes/password.inc';
        $user->pass = user_hash_password(user_password(20));
        // Ensures the user is disabled
        $user->status = 0;
        // Handles user's picture
        $picture = reset($form_state->getValue('picture'));
        if (!empty($picture->fid)) {
            $user->picture = $picture->fid;
        }

        $this->entityManager->getStorage('user')->save($user);

        // Handles site access
        $site = $form_state->getTemporaryValue('site');
        $rid = $form_state->getValue('role');
        $relativeRoles = $this->siteManager->getAccess()->getRelativeRoles();

        if ((int) $relativeRoles[$rid] === Access::ROLE_WEBMASTER) {
            $this->siteManager->getAccess()->addWebmasters($site, $user->id());
        } else {
            $this->siteManager->getAccess()->addContributors($site, $user->id());
        }

        drupal_set_message($this->t("!name has been created and added as %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->siteManager->getAccess()->getDrupalRoleName($rid),
        ]));

        $event = new SiteEvent($site, $this->currentUser()->id(), ['uid' => $user->id()]);
        $this->dispatcher->dispatch('site:add_new_webmaster', $event);
    }
}

