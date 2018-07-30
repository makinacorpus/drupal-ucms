<?php

namespace MakinaCorpus\Ucms\Group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to assign a member to a group
 */
class GroupSiteAdd extends FormBase
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
        return 'ucms_group_site_add_existing';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Group $group = null)
    {
        if (null === $group) {
            return $form;
        }

        $form['name'] = [
            '#type'               => 'textfield',
            '#title'              => $this->t("Title, administrative title, hostname..."),
            '#description'        => $this->t("Please make your choice in the suggestions list."),
            '#autocomplete_path'  => 'admin/dashboard/group/' . $group->getId() . '/sites-add-ac',
            '#required'           => true,
        ];

        $form['group'] = [
            '#type'     => 'value',
            '#value'    => $group->getId(),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'     => 'submit',
            '#value'    => $this->t("Add"),
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
            $form_state->setErrorByName('name', $this->t("The site can't be identified."));
        } else {
            $site = $this->siteManager->getStorage()->findOne($matches[1]);
            if (null === $site) {
                $form_state->setErrorByName('name', $this->t("The site doesn't exist."));
            } else {
                $form_state->setTemporaryValue('site', $site);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        /** @var \MakinaCorpus\Ucms\Site\Site $site */
        $site     = $form_state->getTemporaryValue('site');
        $groupÎd  = $form_state->getValue('group');
        $group    = $this->groupManager->findOne($groupÎd);

        if ($this->groupManager->addSite($groupÎd, $site->getId())) {
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
