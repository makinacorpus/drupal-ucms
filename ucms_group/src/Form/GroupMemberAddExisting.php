<?php
namespace MakinaCorpus\Ucms\Group\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Form to assign a member to a group
 */
class GroupMemberAddExisting extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_group.manager'),
            $container->get('entity.manager'),
            $container->get('event_dispatcher')
        );
    }

    private $groupManager;
    private $entityManager;
    private $dispatcher;

    public function __construct(GroupManager $groupManager, EntityManager $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->groupManager = $groupManager;
        $this->entityManager = $entityManager;
        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_group_member_add_existing';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Group $group = null)
    {
        if (null === $group) {
            return $form;
        }

        $form['#form_horizontal'] = true;

        $form['name'] = [
            '#type'               => 'textfield',
            '#title'              => $this->t("Name"),
            '#description'        => $this->t("Please make your choice in the suggestions list."),
            '#autocomplete_path'  => 'admin/dashboard/ajax/users-ac',
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
        /** @var \Drupal\user\UserInterface $user */
        $user = $form_state->getTemporaryValue('user');

        if ($this->groupManager->addMember($form_state->getValue('group'), $user->id())) {
            drupal_set_message($this->t("!name has been added to group.", [
                '!name' => $user->getDisplayName(),
            ]));
        } else {
            drupal_set_message($this->t("!name is already a member of this group.", [
                '!name' => $user->getDisplayName(),
            ]));
        }
    }
}
