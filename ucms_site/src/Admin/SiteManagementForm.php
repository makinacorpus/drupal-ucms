<?php

namespace MakinaCorpus\Ucms\Site\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\SiteAccessService;

use Symfony\Component\DependencyInjection\ContainerInterface;
use MakinaCorpus\Ucms\Site\Access;

class SiteManagementForm extends FormBase
{
    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self($container->get('ucms_site_access'));
    }

    /**
     * @var SiteAccessService
     */
    private $access;

    /**
     * Default constructor
     *
     * @param SiteAccessService $access
     */
    public function __construct(SiteAccessService $access)
    {
        $this->access = $access;
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
        $form['#form_horizontal'] = true;
        $form['#tree'] = true;

        $options = [];
        foreach (list_themes() as $theme => $data) {
            if ($data->status) {
                $options[$theme] = $data->info['name'];
            }
        }

        $form['themes'] = [
            '#title'          => $this->t("Allowed themes"),
            '#type'           => 'checkboxes',
            '#options'        => $options,
            '#default_value'  => variable_get('ucms_site_allowed_themes', []),
            '#description'    => $this->t("Themes available in the site request form to be choosen by the requester"),
        ];

        $form['sep1']['#markup'] = '<hr/>';

        // @todo
        //   if porting this to D8, this query should be replaced by some API
        //   services giving the right role list instead, hence there is no
        //   dependency on the database service
        $relativeRoles = $this->access->getRelativeRoles();
        foreach ($this->access->getDrupalRoleList() as $rid => $name) {
            $form['roles'][$rid] = [
                '#title'          => $name,
                '#type'           => 'select',
                '#empty_option'   => $this->t("None"),
                '#options'        => [
                    Access::ROLE_WEBMASTER  => $this->t("Webmaster"),
                    Access::ROLE_CONTRIB    => $this->t("Contributor"),
                ],
                '#default_value'  => isset($relativeRoles[$rid]) ? $relativeRoles[$rid] : null,
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
        variable_set('ucms_site_allowed_themes', array_combine($values, $values));

        // And this too
        $values = [];
        foreach ($form_state->getValue('roles') as $rid => $role) {
          if ($role) {
            $values[$rid] = (int)$role;
          }
        }
        $this->access->updateRelativeRoles($values);

        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
