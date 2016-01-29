<?php

namespace MakinaCorpus\Ucms\Site\Admin;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\SiteAccessService;
use MakinaCorpus\Ucms\Site\SiteState;

use Symfony\Component\DependencyInjection\ContainerInterface;

class SiteStateTransitionForm extends FormBase
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
        return 'ucms_site_state_transition_form';
    }

    /**
     * {inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#tree'] = true;

        $form['#theme'] = 'ucms_site_state_transition_form';

        $roleMap  = $this->access->getDrupalRoleList();
        $matrix   = $this->access->getStateTransitionMatrix();

        $stateList = SiteState::getList();
        $s1 = array_keys($stateList);
        // Not sure this is required, but internal pointers may behave wrongly
        $s2 = $s1;
        foreach ($s1 as $d1) {
            foreach ($s2 as $d2) {
                if ($d1 !== $d2) {
                    foreach ($roleMap as $rid => $name) {
                        $form['transitions'][$d1][$d2][$rid] = [
                            '#type'           => 'checkbox',
                            '#title'          => $name,
                            '#attributes'     => ['title' => $this->t("Allow @name to switch site state from @from to @to", [
                                '@name' => $name,
                                '@from' => $stateList[$d1],
                                '@to'   => $stateList[$d2],
                            ])],
                            '#return_value'   => $rid,
                            '#default_value'  => !empty($matrix[$d1][$d2][$rid]),
                        ];
                    }
                } else {
                    // We can't leave this empty else the theming function would
                    // be terribly hard to write
                    $form['transitions'][$d1][$d2] = ['#markup' => ''];
                }
            }
        }

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  =>$this->t('Save configuration')
        ];

        return $form;
    }

    /**
     * {inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->access->updateStateTransitionMatrix($form_state->getValue('transitions'));

        drupal_set_message($this->t('The configuration options have been saved.'));
    }
}
