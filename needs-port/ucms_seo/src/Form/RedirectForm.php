<?php

namespace MakinaCorpus\Ucms\Seo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use MakinaCorpus\Ucms\Seo\Path\RedirectStorageInterface;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Reference a node on a site
 */
class RedirectForm extends FormBase
{
    /**
     * @var \MakinaCorpus\Ucms\Site\SiteManager
     */
    private $siteManager;

    /**
     * @var \MakinaCorpus\Ucms\Seo\Path\RedirectStorageInterface
     */
    private $storage;

    /**
     * {inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_site.manager'),
            $container->get('ucms_seo.redirect_storage')
        );
    }

    /**
     * Default constructor
     *
     * @param SiteManager $siteManager
     * @param RedirectStorageInterface $redirectStorage
     */
    public function __construct(SiteManager $siteManager, RedirectStorageInterface $redirectStorage)
    {
        $this->siteManager = $siteManager;
        $this->storage = $redirectStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_seo_redirect_add';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $node = null)
    {
        if (!$node) {
            $this->logger('form')->critical("There is no node to reference!");

            return $form;
        }

        if (!$this->siteManager->hasContext()) {
            $sites = $this->siteManager->loadWebmasterSites($this->currentUser());
            $options = [];
            foreach ($sites as $site) {
                $options[$site->id] = check_plain($site->title);
            }
            $form['site_id'] = [
                '#type'          => count($options) < 11 ? 'radios' : 'select',
                '#title'         => $this->t("Select a site"),
                '#options'       => $options,
                '#required'      => true,
                '#default_value' => null,
            ];
        } else {
            $form['site_id'] = [
                '#type'  => 'value',
                '#value' => $this->siteManager->getContext()->getId(),
            ];
        }

        $form['path'] = [
            '#type'     => 'textfield',
            '#title'    => $this->t("Old path"),
            '#required' => true,
        ];


        $form_state->setTemporaryValue('node', $node);

        $form['actions']['#type'] = 'actions';
        $form['actions']['continue'] = [
            '#type'  => 'submit',
            '#value' => $this->t("Create redirect"),
        ];
        if (isset($_GET['destination'])) {
            $form['actions']['cancel'] = [
                '#markup' => l(
                    $this->t("Cancel"),
                    $_GET['destination'],
                    ['attributes' => ['class' => ['btn', 'btn-danger']]]
                ),
            ];
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $node   = $form_state->getTemporaryValue('node');
        $siteId = $form_state->getValue('site_id');
        $path   = $form_state->getValue('path');

        if (substr($path, 0, 1) !== '/') {
            $form_state->setError($form['path'], $this->t('The path should begin by a slash ("/")'));
        }
        if ($this->storage->redirectExists($path, $node->nid, $siteId)) {
            $form_state->setError($form['path'], $this->t("This redirect already exist for this node and this site"));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $node   = $form_state->getTemporaryValue('node');
        $siteId = $form_state->getValue('site_id');
        $path   = $form_state->getValue('path');

        $this->storage->save($path, $node->nid, $siteId);

        drupal_set_message(
            $this->t(
                "%path will redirect to %title",
                [
                    '%path'  => $path,
                    '%title' => $node->title,
                ]
            )
        );

        $form_state->setRedirect('node/'.$node->nid);
    }
}
