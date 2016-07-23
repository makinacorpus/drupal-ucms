<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MakinaCorpus\Umenu\Menu;

class TreeEditForm extends FormBase
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
        $form['#form_horizontal'] = true;

        $isCreation = false;

        if (!$menu) {
            $isCreation = true;
            $menu = new Menu();
        }

        $form['name'] = [
            '#type'           => 'textfield',
            '#attributes'     => ['placeholder' => 'site-42-main'],
            '#description'    => $this->t("Leave this empty for auto-generation"),
            '#default_value'  => $menu->getName(),
            '#maxlength'      => 255,
            '#disabled'       => !$isCreation,
        ];

        $form['is_creation'] = ['#type' => 'value', '#value' => $isCreation];

        $form['title'] = [
            '#type'           => 'textfield',
            '#title'          => $this->t("Title"),
            '#attributes'     => ['placeholder' => $this->t("Main, Footer, ...")],
            '#default_value'  => $menu->getTitle(),
            '#required'       => true,
            '#maxlength'      => 255,
        ];

        $form['description'] = [
            '#type'           => 'textfield',
            '#title'          => $this->t("Description"),
            '#attributes'     => ['placeholder' => $this->t("Something about this menu...")],
            '#default_value'  => $menu->getDescription(),
            '#maxlength'      => 1024,
        ];

        $form['is_main'] = [
            '#type'           => 'checkbox',
            '#title'          => $this->t("Is this menu the site main menu?"),
            '#attributes'     => ['placeholder' => $this->t("Something about this menu...")],
            '#default_value'  => $menu->isSiteMain(),
            '#description'    => $this->t("If the site already has a main menu, this will change it."),
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

            $storage = $this->treeManager->getMenuStorage();
            $name = $form_state->getValue('name');

            $values = [
                'title'       => $form_state->getValue('title'),
                'description' => $form_state->getValue('description'),
            ];

            if ($this->siteManager->hasContext()) {
                $siteId = $this->siteManager->getContext()->getId();
                $values['site_id'] = $siteId;

                if (!$name) {
                    // Auto-generation for name
                    $name = \URLify::filter('site-' . $siteId . '-' . $values['title'], 255);
                }
            } else if (!$name) {
                // Auto-generation for name
                $name = \URLify::filter($values['title'], 255);
            }

            if ($form_state->getValue('is_creation')) {
                $storage->create($name, $values);
            } else {
                $storage->update($name, $values);
            }
            $storage->setMainMenuStatus($name, (bool)$form_state->getValue('is_main'));

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
