<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use MakinaCorpus\Ucms\Dashboard\Form\FormHelper;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\TreeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MenuAddItemForm extends FormBase
{
    protected $database;
    protected $entitytTypeManager;
    protected $menu;
    protected $treeManager;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('umenu.manager'),
            $container->get('entity_type.manager'),
            $container->get('database')
        );
    }

    /**
     * TreeForm constructor
     */
    public function __construct(
        TreeManager $treeManager,
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database
    ) {
        $this->database = $database;
        $this->entitytTypeManager = $entityTypeManager;
        $this->treeManager = $treeManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormId()
    {
        return 'ucms_tree_menu_edit_form';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Menu $menu = null)
    {
        $this->menu = $menu;

        $form['node'] = [
            '#type' => 'nodesearch',
            '#title' => new TranslatableMarkup("Title"),
            '#required' => true,
            '#multiple' => false,
        ];

        $form['title'] = [
            '#type' => 'textfield',
            '#attributes' => ['placeholder' => new TranslatableMarkup('Some title')],
            '#description' => new TranslatableMarkup("Leave this field empty to use the selected content title"),
            '#maxlength' => 255,
        ];

        $form['position'] = [
            '#type' => 'select',
            '#options' => [
                -1 => new TranslatableMarkup("Prepend"),
                1 => new TranslatableMarkup("Append"),
            ],
            '#default_value' => 1,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'  => 'submit',
            '#value' => new TranslatableMarkup('Add'),
        ];
        if ($siteId = $this->menu->getSiteId()) {
            $form['actions']['cancel'] = FormHelper::createCancelLink(new Url('ucms_tree.admin.menu.list', ['site' => $siteId]));
        } else {
            $form['actions']['cancel'] = FormHelper::createCancelLink(new Url('ucms_tree.admin.menu.tree', ['menu' => $this->menu->getId()]));
        }

        return $form;
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $tx = $this->database->startTransaction();

            $nodeId = $form_state->getValue('node');
            $nodeId = (int)(\is_array($nodeId) ? reset($nodeId) : $nodeId);

            $storage = $this->treeManager->getItemStorage();
            $storage->insert($this->menu->getId(), $nodeId, $form_state->getValue('title'));

            unset($tx); // Explicit commit.

            \drupal_set_message(new TranslatableMarkup("Tree has been saved"));

        } catch (\Throwable $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {
                    \watchdog_exception('ucms_tree', $e2);
                }
                \watchdog_exception('ucms_tree', $e);
                \drupal_set_message($this->t("Could not save tree"), 'error');
            }
        }
    }
}
