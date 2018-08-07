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

        $form['ga_id'] = [
            '#title'            => t("Google analytics identifier"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => 'UA-123456'],
            '#default_value'    => $site->getAttribute('seo.google.ga_id'),
        ];
        $form['gtm_id'] = [
            '#title'            => t("Google tag manager identifier"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => 'GTM-123456'],
            '#default_value'    => $site->getAttribute('seo.google.gtm_id'),
        ];

        $form['piwik']['#tree'] = true;
        $form['piwik']['url'] = [
            '#title'            => t("Piwik URL"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => 'demo.piwik.org'],
            '#default_value'    => $site->getAttribute('seo.piwik.url'),
        ];
        $form['piwik']['site_id'] = [
            '#title'            => t("Piwik site identifier"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => '123'],
            '#default_value'    => $site->getAttribute('seo.piwik.site_id'),
        ];

        $form['site_verification'] = [
            '#title'            => t("Google webmaster tools verification code"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => '+nxGUDJ4QpAZ5l9Bsjdi102tLVC21AIh5d1Nl23908vVuFHs34='],
            '#default_value'    => $site->getAttribute('seo.google.site_verification'),
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
        if ($gtmId = $formState->getValue('gtm_id')) {
            $site->setAttribute('seo.google.gtm_id', $gtmId);
        } else {
            $site->deleteAttribute('seo.google.gtm_id');
        }

        if ($url = $formState->getValue(['piwik', 'url'])) {
            $site->setAttribute('seo.piwik.url', $url);
        } else {
            $site->deleteAttribute('seo.piwik.url');
        }
        if ($siteId = $formState->getValue(['piwik', 'site_id'])) {
            $site->setAttribute('seo.piwik.site_id', $siteId);
        } else {
            $site->deleteAttribute('seo.piwik.site_id');
        }

        if ($siteVerif = $formState->getValue('site_verification')) {
            $site->setAttribute('seo.google.site_verification', $siteVerif);
        } else {
            $site->deleteAttribute('seo.google.site_verification');
        }

        $this->siteManager->getStorage()->save($site, ['attributes']);
    }
}
