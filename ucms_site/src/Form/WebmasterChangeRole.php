<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Url;
use MakinaCorpus\Ucms\Dashboard\Form\FormHelper;

class WebmasterChangeRole extends FormBase
{
    /**
     * {@inheritdoc}
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
    protected $user;

    /**
     * Default constructor
     */
    public function __construct(SiteManager $siteManager, EntityManager $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
        $this->entityManager = $entityManager;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_webmaster_change_role';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null, AccountInterface $user = null)
    {
        if (null === $site || null === $user) {
            return [];
        }

        $this->site = $site;
        $this->user = $user;

        $accessService = $this->siteManager->getAccess();

        $form['role'] = [
            '#type' => 'radios',
            '#title' => $this->t("Role"),
            '#options' => $accessService->getSiteRoles($this->site),
            '#default_value' => $accessService->getUserRole($user, $site)->getRole(),
            '#required' => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t("Validate"),
        ];
        $form['actions']['cancel'] = FormHelper::createCancelLink(new Url('ucms_site.admin.site.webmaster', ['site' => $this->site->getId()]));

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $site = $this->site;
        $user = $this->user;
        $role = (int)$form_state->getValue('role');

        $oldAccess = $this->siteManager->getAccess()->getUserRole($user, $site);
        $this->siteManager->getAccess()->mergeUsersWithRole($site, [$user->id()], $role);

        \drupal_set_message($this->t("@name role has been changed from @prev to @role.", [
            '@name' => $user->getDisplayName(),
            '@prev' => $this->siteManager->getAccess()->getRoleName($oldAccess->getRole()),
            '@role' => $this->siteManager->getAccess()->getRoleName($role),
        ]));

        $event = new SiteEvent($site, $this->currentUser()->id(), [
            'webmaster_id' => $user->id(),
            'previous_role' => $oldAccess->getRole(),
        ]);
        $this->dispatcher->dispatch('site:webmaster_change_role', $event);

        $form_state->setRedirect('ucms_site.admin.site.webmaster', ['site' => $this->site->getId()]);
    }
}
