<?php

namespace MakinaCorpus\Ucms\Tree\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Umenu\Menu;
use MakinaCorpus\Umenu\TreeManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

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
            '#description'    => $this->t("Leave this field empty for auto-generation"),
            '#default_value'  => $menu->getName(),
            '#maxlength'      => 255,
            '#disabled'       => !$isCreation,
        ];

        $form['is_creation'] = ['#type' => 'value', '#value' => $isCreation];

        if ($roles = variable_get('umenu_allowed_roles', [])) {
            $form['role'] = [
                '#type'           => 'select',
                '#title'          => $this->t("Role"),
                '#options'        => $roles,
                '#default_value'  => $menu->getRole(),
                '#empty_option'   => $this->t("None"),
                '#required'       => true,
                '#maxlength'      => 255,
            ];
        }

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

        $allowedRoles = ucms_tree_role_list();
        if ($allowedRoles) {
            $form['role'] = [
                '#type'           => 'select',
                '#title'          => $this->t("Menu role in site"),
                '#empty_option'   => $this->t("None"),
                '#options'        => $allowedRoles,
                '#default_value'  => $menu->getRole(),
            ];
        }

        $form['is_main'] = [
            '#type'           => 'checkbox',
            '#title'          => $this->t("Set as the site main menu?"),
            '#default_value'  => $menu->isSiteMain(),
            '#description'    => $this->t("If the site already has a main menu, this choice will change it."),
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
                'role'        => $form_state->getValue('role'),
            ];

            if ($this->siteManager->hasContext()) {
                $siteId = $this->siteManager->getContext()->getId();
                $values['site_id'] = $siteId;

                if (!$name) {
                    // Auto-generation for name
                    $name = \URLify::filter('site-' . $siteId . '-' . $values['title'], UCMS_SEO_SEGMENT_TRIM_LENGTH);
                }
            } else if (!$name) {
                // Auto-generation for name
                $name = \URLify::filter($values['title'], UCMS_SEO_SEGMENT_TRIM_LENGTH);
            }

            if ($form_state->getValue('is_creation')) {
                $storage->create($name, $values);
            } else {
                $storage->update($name, $values);
            }
            $storage->toggleMainStatus($name, (bool)$form_state->getValue('is_main'));

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
