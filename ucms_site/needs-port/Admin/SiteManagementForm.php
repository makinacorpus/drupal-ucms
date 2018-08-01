<?php

namespace MakinaCorpus\Ucms\Site\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteManagementForm extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_site.manager'));
    }

    /**
     * @var SiteManager
     */
    private $manager;

    /**
     * Default constructor
     *
     * @param SiteManager $manager
     */
    public function __construct(SiteManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_management_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#tree'] = true;

        $options = [];
        foreach (list_themes() as $theme => $data) {
            if ($data->status) {
                $options[$theme] = $data->info['name'];
            }
        }

        $allowed = $this->manager->getAllowedThemes();
        $form['themes'] = [
            '#title'          => $this->t("Allowed themes"),
            '#type'           => 'checkboxes',
            '#options'        => $options,
            '#default_value'  => array_combine($allowed, $allowed),
            '#description'    => $this->t("Themes available in the site request form to be choosen by the requester"),
        ];

        $form['sep1']['#markup'] = '<hr/>';

        // @todo
        //   if porting this to D8, this query should be replaced by some API
        //   services giving the right role list instead, hence there is no
        //   dependency on the database service
        $rolesAssociations = $this->manager->getAccess()->getRolesAssociations();
        $relativeRoles = $this->manager->getAccess()->collectRelativeRoles();

        foreach ($this->manager->getAccess()->getDrupalRoleList() as $rid => $name) {
            $form['roles'][$rid] = [
                '#title'          => $name,
                '#type'           => 'select',
                '#empty_option'   => $this->t("None"),
                '#options'        => $relativeRoles,
                '#default_value'  => isset($rolesAssociations[$rid]) ? $rolesAssociations[$rid] : null,
            ];
        }

        $form['sep2']['#markup'] = '<hr/>';

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t('Save configuration')
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        // This works because no values are empty()
        $values = array_keys(array_filter($form_state->getValue('themes')));
        $this->manager->setAllowedThemes($values);

        // And this too
        $values = [];
        foreach ($form_state->getValue('roles') as $rid => $role) {
          if ($role) {
            $values[$rid] = (int)$role;
          }
        }
        $this->manager->getAccess()->updateRolesAssociations($values);

        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
