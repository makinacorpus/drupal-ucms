<?php

namespace MakinaCorpus\Ucms\Group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to assign a member to a group
 */
class SiteGroupAttach extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_group.manager'),
            $container->get('ucms_site.manager'),
            $container->get('event_dispatcher')
        );
    }

    private $groupManager;
    private $siteManager;
    private $dispatcher;

    public function __construct(GroupManager $groupManager, SiteManager $siteManager, EventDispatcherInterface $dispatcher)
    {
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_group_site_attach';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Site $site = null)
    {
        if (null === $site) {
            return $form;
        }

        $form['#form_horizontal'] = true;

        $form['name'] = [
            '#type'               => 'textfield',
            '#title'              => $this->t("Group title..."),
            '#description'        => $this->t("Please make your choice in the suggestions list."),
            '#autocomplete_path'  => 'admin/dashboard/site/' . $site->getId() . '/group-attach/ac',
            '#required'           => true,
        ];

        $form['site'] = [
            '#type'     => 'value',
            '#value'    => $site->getId(),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'     => 'submit',
            '#value'    => $this->t("Attach"),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $string = $form_state->getValue('name');

        $matches = [];
        if (preg_match('/\[(\d+)\]$/', $string, $matches) !== 1 || $matches[1] < 2) {
            $form_state->setErrorByName('name', $this->t("The group can't be identified."));
        } else {
            $group = $this->groupManager->getStorage()->findOne($matches[1]);
            if (null === $group) {
                $form_state->setErrorByName('name', $this->t("The group doesn't exist."));
            } else {
                $form_state->setTemporaryValue('group', $group);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /** @var \MakinaCorpus\Ucms\Group\Group $group */
        $group  = $form_state->getTemporaryValue('group');
        $siteId = $form_state->getValue('site');
        $site   = $this->siteManager->getStorage()->findOne($siteId);

        if ($this->groupManager->getAccess()->addSite($group->getId(), $siteId, true)) {
            drupal_set_message($this->t("%name has been added to group %group.", [
                '%name' => $site->getAdminTitle(),
                '%group'  => $group->getTitle(),
            ]));
        } else {
            drupal_set_message($this->t("%name is already in this group %group.", [
                '%name'   => $site->getAdminTitle(),
                '%group'  => $group->getTitle(),
            ]));
        }
    }
}
