<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvents;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to assign a webmaster/contributor to a site.
 */
class WebmasterAddExisting extends FormBase
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

    private $dispatcher;
    private $entityManager;
    private $siteManager;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, EntityManager $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->siteManager = $siteManager;
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_webmaster_add_existing';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        if (null === $site) {
            return [];
        }

        $form['#form_horizontal'] = true;

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t("Name"),
            '#description' => $this->t("Please make your choice in the suggestions list."),
            '#autocomplete_path' => 'admin/dashboard/ajax/users-ac',
            '#required' => true,
        ];

        $roles = $this->siteManager->getAccess()->collectRelativeRoles($site);

        $form['role'] = [
            '#type' => 'radios',
            '#title' => $this->t("Role"),
            '#options' => $roles,
            '#required' => true,
        ];

        $form['site'] = [
            '#type' => 'value',
            '#value' => $site->id,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t("Add"),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getValue('name');

        $matches = [];
        if (preg_match('/\[(\d+)\]$/', $user, $matches) !== 1 || $matches[1] < 2) {
            $form_state->setErrorByName('name', $this->t("The user can't be identified."));
        } else {
            $user = $this->entityManager->getStorage('user')->load($matches[1]);
            if (null === $user) {
                $form_state->setErrorByName('name', $this->t("The user doesn't exist."));
            } else {
                $form_state->setTemporaryValue('user', $user);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /* @var Site $site */
        $site = $this->siteManager->getStorage()->findOne($form_state->getValue('site'));
        $user = $form_state->getTemporaryValue('user');

        $role = $form_state->getValue('role');
        $this->siteManager->getAccess()->mergeUsersWithRole($site, $user->id(), $role);

        drupal_set_message($this->t("!name has been added as %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->siteManager->getAccess()->getRelativeRoleName($role),
        ]));

        $event = new SiteEvent($site, $this->currentUser()->id(), ['webmaster_id' => $user->id()]);
        $this->dispatcher->dispatch(SiteEvents::EVENT_WEBMASTER_ATTACH, $event);
    }
}
