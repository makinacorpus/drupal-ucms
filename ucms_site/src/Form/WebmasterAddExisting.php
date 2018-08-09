<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Form\FormHelper;
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

    protected $dispatcher;
    protected $entityManager;
    protected $site;
    protected $siteManager;

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

        $this->site = $site;

        $form['name'] = [
            '#type' => 'textfield',
            '#title' => new TranslatableMarkup("Name"),
            '#description' => new TranslatableMarkup("Please make your choice in the suggestions list."),
            '#autocomplete_route_name' => 'ucms_site.admin.user_autocomplete',
            '#required' => true,
        ];

        $form['role'] = [
            '#type' => 'radios',
            '#title' => new TranslatableMarkup("Role"),
            '#options' => $this->siteManager->getAccess()->getSiteRoles($this->site),
            '#required' => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => new TranslatableMarkup("Add"),
        ];
        $form['actions']['cancel'] = FormHelper::createCancelLink(new Url('ucms_site.admin.site.webmaster', ['site' => $this->site->getId()]));

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $user = $form_state->getValue('name');

        $matches = [];
        if (\preg_match('/\[(\d+)\]$/', $user, $matches) !== 1 || $matches[1] < 2) {
            $form_state->setErrorByName('name', new TranslatableMarkup("The user can't be identified."));
        } else {
            $user = $this->entityManager->getStorage('user')->load($matches[1]);
            if (null === $user) {
                $form_state->setErrorByName('name', new TranslatableMarkup("The user doesn't exist."));
            } else {
                $form_state->setTemporaryValue('user', $user);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /** @var \Drupal\Core\Session\AccountInterface $user */
        $user = $formState->getTemporaryValue('user');

        $role = (int)$formState->getValue('role');
        $this->siteManager->getAccess()->mergeUsersWithRole($this->site, $user->id(), $role);

        \drupal_set_message(new TranslatableMarkup("@name has been added as @role.", [
            '@name' => $user->getDisplayName(),
            '@role' => $this->siteManager->getAccess()->getRoleName($role),
        ]));

        $event = new SiteEvent($this->site, $this->currentUser()->id(), ['webmaster_id' => $user->id()]);
        $this->dispatcher->dispatch(SiteEvents::EVENT_WEBMASTER_ATTACH, $event);

        $formState->setRedirect('ucms_site.admin.site.webmaster', ['site' => $this->site->getId()]);
    }
}
