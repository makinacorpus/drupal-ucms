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

class MenuAddMultipleItemForm extends FormBase
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
        return 'ucms_tree_menu_add_multiple_item_form';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Menu $menu = null)
    {
        $this->menu = $menu;

        $form['nodes'] = [
            '#type' => 'nodesearch',
            '#title' => new TranslatableMarkup("Title"),
            '#required' => true,
            '#multiple' => true,
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

            $storage = $this->treeManager->getItemStorage();
            $values = $form_state->getValue('nodes', []);

            $nodes = $this->entitytTypeManager->getStorage('node')->loadMultiple($values);
            /** @var \Drupal\node\NodeInterface $node */
            foreach ($nodes as $node) {
                $storage->insert($this->menu->getId(), $node->id(), $node->getTitle());
            }

            unset($tx); // Explicit commit.

            \drupal_set_message(new TranslatableMarkup("Items have been added"));

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
