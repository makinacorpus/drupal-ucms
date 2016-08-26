<?php


namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

use MakinaCorpus\Ucms\Site\EventDispatcher\SiteEvent;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;


/**
 * Form to change the role of a site's user.
 */
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
        return 'ucms_webmaster_change_role';
    }


    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, Site $site = null, AccountInterface $user = null)
    {
        if (null === $site || null === $user) {
            return [];
        }

        $formState->setTemporaryValue('site', $site);
        $formState->setTemporaryValue('user', $user);

        $form['#form_horizontal'] = true;

        $roles = [];
        $relativeRoles = $this->siteManager->getAccess()->collectRelativeRoles($site);
        $rolesAssociations = $this->siteManager->getAccess()->getRolesAssociations();
        $userRelativeRole = $this->siteManager->getAccess()->getUserRole($user, $site);
        $userDrupalRole = null;

        foreach ($rolesAssociations as $rid => $rrid) {
            if (isset($relativeRoles[$rrid])) {
                $roles[$rid] = $this->siteManager->getAccess()->getDrupalRoleName($rid);
                if ($rrid == $userRelativeRole->getRole()) {
                    $userDrupalRole = $rid;
                }
            }
        }

        $form['role'] = [
            '#type' => 'radios',
            '#title' => $this->t("Role"),
            '#options' => $roles,
            '#default_value' => $userDrupalRole,
            '#required' => true,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t("Validate"),
        ];

        return $form;
    }


    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /* @var Site $site */
        $site = $formState->getTemporaryValue('site');
        $user = $formState->getTemporaryValue('user');
        $rid  = $formState->getValue('role');

        $oldAccess = $this->siteManager->getAccess()->getUserRole($user, $site);
        $rolesAssociations = $this->siteManager->getAccess()->getRolesAssociations();
        $this->siteManager->getAccess()->mergeUsersWithRole($site, $user->id(), $rolesAssociations[$rid]);

        drupal_set_message($this->t("!name is from now on %role.", [
            '!name' => $user->getDisplayName(),
            '%role' => $this->siteManager->getAccess()->getDrupalRoleName($rid),
        ]));

        $event = new SiteEvent($site, $this->currentUser()->id(), [
            'webmaster_id' => $user->id(),
            'previous_role' => $oldAccess->getRole(),
        ]);
        $this->dispatcher->dispatch('site:webmaster_change_role', $event);
    }
}

