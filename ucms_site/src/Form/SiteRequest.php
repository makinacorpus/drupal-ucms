<?php

namespace MakinaCorpus\Ucms\Site\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Site\Site;

/**
 * Request site creation form
 */
class SiteRequest extends FormBase
{
    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_site_request';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $storage = &$form_state->getStorage();

        if (empty($storage['site'])) {
            $site = $storage['site'] = new Site();
            $site->uid = $this->currentUser()->uid;
        } else {
            $site = $storage['site'];
        }

        if (empty($storage['step'])) {
            $step = 'a';
        } else {
            $step = $storage['step'];
        }

        switch ($step) {

          case 'a':
              return $this->buildStepA($form, $form_state, $site);
              break;

          case 'b':
              return $this->buildStepB($form, $form_state, $site);
              break;
        }

        // This is an error...
        $this->logger('form')->critical("Invalid step @step", ['@step' => $step]);

        return $form;
    }

    /**
     * Step A form builder
     */
    private function buildStepA(array $form, FormStateInterface $form_state, Site $site)
    {
        $form['title'] = [
            '#title'          => $this->t("Name"),
            '#type'           => 'textfield',
            '#default_value'  => $site->title,
            '#attributes'     => ['placeholder' => t("Martray's optical")],
            '#description'    => $this->t("This will appear on the site as the site title"),
            '#required'       => true,
        ];

        $form['title_admin'] = [
            '#title'          => $this->t("Description"),
            '#type'           => 'textarea',
            '#default_value'  => $site->title_admin,
            '#description'    => $this->t("This will be as the site's administrative description in platform backoffice"),
            '#required'       => true,
        ];

        $form['http_host'] = [
            '#title'          => t("Host name"),
            '#type'           => 'textfield',
            '#field_prefix'   => "http://",
            '#default_value'  => $site->http_host,
            '#attributes'     => ['placeholder' => "martray-optique.fr"],
            '#description'    => $this->t("Type here the site URL"),
            '#required'       => true,
        ];

        // @todo Missing site type

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Continue"),
            '#submit' => ['::submitStepA'],
        ];
        $form['actions']['cancel'] = [
            '#markup' => l(
                $this->t("Cancel"),
                isset($_GET['destination']) ? $_GET['destination'] : 'admin/dashboard/site',
                ['attributes' => ['class' => ['btn', 'btn-danger']]]
            ),
        ];

        return $form;
    }

    /**
     * Step B form validate
     */
    public function validateStepA(array $form, FormStateInterface $form_state)
    {
        // @todo
        //   http_host validation (unique and valid)
        //   
    }

    /**
     * Step B form submit
     */
    public function submitStepA(array $form, FormStateInterface $form_state)
    {
        $storage  = &$form_state->getStorage();
        $values   = &$form_state->getValues();

        /** @var $site Site */
        $site               = $storage['site'];
        $site->title        = $values['title'];
        $site->title_admin  = $values['title_admin'];
        $site->http_host    = $values['http_host'];

        $storage['step'] = 'b';
        $form_state->setRebuild(true);
    }

    /**
     * Step B form builder
     */
    private function buildStepB(array $form, FormStateInterface $form_state, Site $site)
    {
        // @todo Form stuff

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Request"),
        ];
        $form['actions']['back'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Go back"),
            '#submit' => ['::submitStepABack'],
        ];

        return $form;
    }

    /**
     * Step B form go back submit
     */
    public function submitStepABack(array $form, FormStateInterface $form_state)
    {
        $storage = &$form_state->getStorage();
        // @todo Set values in form state
        $storage['step'] = 'a';
        $form_state->setRebuild(true);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $storage  = &$form_state->getStorage();

        /** @var $site Site */
        $site = $storage['site'];
        $site->state = 0;

        ucms_site_finder()->save($site);
        drupal_set_message($this->t("Your site creation request has been submitted"));
    }
}
