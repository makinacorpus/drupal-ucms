<?php

namespace MakinaCorpus\Ucms\Group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Group\Group;
use MakinaCorpus\Ucms\Group\GroupManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Request site creation form
 */
class GroupEdit extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_group.manager')
        );
    }

    private $groupManager;
    private $dispatcher;

    public function __construct(GroupManager $groupManager)
    {
        $this->groupManager = $groupManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_group_edit';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Group $group = null)
    {
        $form['#form_horizontal'] = true;

        if (!$group) {
            $group = new Group();
        }

        $form_state->setTemporaryValue('group', $group);

        $form['title'] = [
            '#title'          => $this->t("Name"),
            '#type'           => 'textfield',
            '#default_value'  => $group->getTitle(),
            '#attributes'     => ['placeholder' => $this->t("Ouest union")],
            '#maxlength'      => 255,
            '#required'       => true,
        ];

        $form['is_ghost'] = [
            '#title'          => $this->t("Content is invisible in global content"),
            '#type'           => 'checkbox',
            '#default_value'  => $group->isGhost(),
            '#description'    => $this->t("This is only the default value for content, it may be changed on a per-content basis."),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Save"),
        ];
        $form['actions']['cancel'] = [
            '#markup' => l(
                $this->t("Cancel"),
                isset($_GET['destination']) ? $_GET['destination'] : 'admin/dashboard/group',
                ['attributes' => ['class' => ['btn', 'btn-danger']]]
            ),
        ];

        return $form;
    }

    /**
     * Step B form submit
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $group   = &$form_state->getTemporaryValue('group');
        $values  = &$form_state->getValues();

        /** @var $group Group */
        $group->setTitle($values['title']);
        $group->setIsGhost($values['is_ghost']);

        $isNew = !$group->getId();

        $this->groupManager->getStorage()->save($group, ['title', 'is_ghost']);
        if ($isNew) {
            drupal_set_message($this->t("Group %title has been created", ['%title' => $group->getTitle()]));
        } else {
            drupal_set_message($this->t("Group %title has been updated", ['%title' => $group->getTitle()]));
        }

        $form_state->setRedirect('admin/dashboard/group/' . $group->getId());
    }
}
