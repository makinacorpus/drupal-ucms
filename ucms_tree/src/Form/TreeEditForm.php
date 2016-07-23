<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MakinaCorpus\Umenu\Menu;

class TreeForm extends FormBase
{
    private $treeManager;
    private $siteManager;
    private $db;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('umenu.manager'),
            $container->get('ucms_site.manager'),
            $container->get('database')
        );
    }

    /**
     * TreeForm constructor.
     *
     * @param TreeManager $treeManager
     * @param SiteManager $siteManager
     * @param \DatabaseConnection $db
     */
    public function __construct(TreeManager $treeManager, SiteManager $siteManager, \DatabaseConnection $db)
    {
        $this->treeManager = $treeManager;
        $this->siteManager = $siteManager;
        $this->db = $db;
    }

    /**
     * {@inheritDoc}
     */
    public function getFormId()
    {
        return 'ucms_tree_tree_edit_form';
    }

    /**
     * {@inheritDoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, Menu $menu = null)
    {
        // Load all menus for site.
        $form_state->setTemporaryValue('menu', $menu);

        $form['title'] = [
            '#type'           => 'textfield',
            '#attributes'     => ['placeholder' => $this->t("Main, Footer, ...")],
            '#default_value'  => $menu->getTitle(),
            '#required'       => true,
            '#maxlength'      => 255,
        ];

        $form['description'] = [
            '#type'           => 'textfield',
            '#attributes'     => ['placeholder' => $this->t("Something about this menu...")],
            '#default_value'  => $menu->getTitle(),
            '#required'       => true,
            '#maxlength'      => 1024,
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'  => 'submit',
            '#value' => $this->t('Save'),
        ];

        return $form;
    }

    /**
     * {@inheritDoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        try {
            $tx = $this->db->startTransaction();

            //$this->treeManager->getMenuStorage()->update($name, $values);

            unset($tx);

            drupal_set_message($this->t("Tree has been saved"));

        } catch (\Exception $e) {
            if ($tx) {
                try {
                    $tx->rollback();
                } catch (\Exception $e2) {
                    watchdog_exception('ucms_tree', $e2);
                }
                watchdog_exception('ucms_tree', $e);

                drupal_set_message($this->t("Could not save tree"), 'error');
            }
        }

    }
}
