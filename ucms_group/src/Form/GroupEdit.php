<?php

namespace MakinaCorpus\Ucms\Group\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Contrib\TypeHandler;
use MakinaCorpus\Ucms\Site\Group;
use MakinaCorpus\Ucms\Site\GroupManager;
use MakinaCorpus\Ucms\Site\SiteManager;
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
        if ($container->has('ucms_contrib.type_handler')) {
            return new self(
                $container->get('ucms_group.manager'),
                $container->get('ucms_site.manager'),
                $container->get('ucms_contrib.type_handler')
            );
        } else {
            return new self(
                $container->get('ucms_group.manager'),
                $container->get('ucms_site.manager')
            );
        }
    }

    private $groupManager;
    private $siteManager;
    private $typeHandler;

    public function __construct(GroupManager $groupManager, SiteManager $siteManager, TypeHandler $typeHandler = null)
    {
        $this->groupManager = $groupManager;
        $this->siteManager = $siteManager;
        $this->typeHandler = $typeHandler;
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

        $allThemeList = $this->siteManager->getDefaultAllowedThemesOptionList();
        $form['allowed_themes'] = [
            '#title'          => $this->t("Allowed themes for group"),
            '#type'           => 'checkboxes',
            '#options'        => $allThemeList,
            '#default_value'  => $group->getAttribute('allowed_themes', []),
            '#description'    => $this->t("Check at least one theme here to restrict allowed themes for this group."),
        ];

        if ($this->typeHandler) {
            if ($allTypes = $this->typeHandler->getAllTypes()) {
                $form['allowed_content_types'] = [
                    '#title'          => $this->t("Allowed content types for group"),
                    '#type'           => 'checkboxes',
                    '#options'        => $this->typeHandler->getTypesAsHumanReadableList($allTypes),
                    '#default_value'  => $group->getAttribute('allowed_content_types', []),
                    '#description'    => $this->t("Check at least one content type here to restrict allowed content types for this group."),
                ];
            }
        }

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

        foreach (['allowed_themes', 'allowed_content_types'] as $name) {
            $list = $form_state->getValue($name, []);
            $list = array_values(array_filter($list));
            if ($list) {
                $group->setAttribute($name, $list);
            } else {
                $group->deleteAttribute($name);
            }
        }

        $isNew = !$group->getId();

        $this->groupManager->save($group, ['title', 'is_ghost', 'attributes']);
        if ($isNew) {
            drupal_set_message($this->t("Group %title has been created", ['%title' => $group->getTitle()]));
        } else {
            drupal_set_message($this->t("Group %title has been updated", ['%title' => $group->getTitle()]));
        }

        $form_state->setRedirect('admin/dashboard/group/' . $group->getId());
    }
}
