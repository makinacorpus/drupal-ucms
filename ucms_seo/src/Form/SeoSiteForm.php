<?php

namespace MakinaCorpus\Ucms\Seo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

class SeoSiteForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_seo.seo_service'),
            $container->get('ucms_site.manager')
        );
    }

    /**
     * @var SeoService
     */
    private $seoService;

    /**
     * @var SiteManager
     */
    private $siteManager;

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'ucms_seo_site_form';
    }

    /**
     * Default constructor
     *
     * @param SeoService $seoService
     */
    public function __construct(SeoService $seoService, SiteManager $siteManager)
    {
        $this->seoService = $seoService;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, Site $site = null)
    {
        if (!$site) {
            return $form;
        }

        $formState->setTemporaryValue('site', $site);

        $form['#form_horizontal'] = true;

        $form['ga_id'] = [
            '#title'            => t("Google analytics identifier"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => 'UA-123456'],
            '#default_value'    => $site->getAttribute('seo.google.ga_id'),
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Submit"),
        ];

        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /* @var $site Site */
        $site = $formState->getTemporaryValue('site');

        if ($gaId = $formState->getValue('ga_id')) {
            $site->setAttribute('seo.google.ga_id', $gaId);
        } else {
            $site->deleteAttribute('seo.google.ga_id');
        }

        $this->siteManager->getStorage()->save($site, ['attributes']);
    }
}
