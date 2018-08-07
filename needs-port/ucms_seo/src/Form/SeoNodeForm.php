<?php

namespace MakinaCorpus\Ucms\Seo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Seo\SeoService;
use MakinaCorpus\Ucms\Site\SiteManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SEO information node edit form.
 */
class SeoNodeForm extends FormBase
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
        return 'ucms_seo_node_form';
    }

    /**
     * Default constructor
     *
     * @param SeoService $seoService
     * @param SiteManager $siteManager
     */
    public function __construct(SeoService $seoService, SiteManager $siteManager)
    {
        $this->seoService = $seoService;
        $this->siteManager = $siteManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $formState, NodeInterface $node = null)
    {
        if (!$node) {
            return $form;
        }

        $formState->setTemporaryValue('node', $node);

        // @todo
        //   fetch menu links for this node, in site context, in order to prefix the form field

        $currentSegment = $this->seoService->getNodeSegment($node);
        $nodeId = $node->id();
        $meta = $this->seoService->getNodeMeta($node) + ['title' => null, 'description' => null];

        $form['segment'] = [
            '#title'            => t("Alias"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => $this->t("about-us")],
            '#default_value'    => $currentSegment,
            '#element_validate' => ['::validateSegment'],
            '#description'      => $this->t("This is the content alias that will be used in order to build URLs for the menu of your site"),
            '#field_prefix'     => 'some/path/', // @todo
        ];

        if ($this->siteManager->hasContext()) {

            $site         = $this->siteManager->getContext();
            $siteId       = $site->getId();
            $aliasManager = $this->seoService->getAliasManager();
            $currentAlias = $aliasManager->getPathAlias($nodeId, $siteId);
            $isProtected  = $currentAlias && $aliasManager->isPathAliasProtected($nodeId, $siteId);

            $form['custom_alias'] = [
                '#title'            => t("Alias in the current site"),
                '#type'             => 'textfield',
                '#attributes'       => ['placeholder' => ($currentAlias ? $currentAlias : $this->t("There is no computed alias yet"))],
                '#default_value'    => $isProtected ? $currentAlias : null,
                '#description'      => $this->t("Generated URL, if you change it, it won't be automatically generated anymore, to make it automatic back again, empty this field"),
                '#field_prefix'     => 'http://' . $site->getHostname() . '/',
            ];
        }

        $form['sep1']['#markup'] = '<hr/>';

        $form['meta_title'] = [
            '#title'            => t("Meta title"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => $this->t("My page title")],
            '#default_value'    => $meta['title'],
            '#description'      => $this->t("This title will be used by search engines to index you content"),
            '#maxlength'        => 68,
        ];
        $form['meta_description'] = [
            '#title'            => t("Meta description"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => $this->t("Shortly, this page is about something that...")],
            '#default_value'    => $meta['description'],
            '#description'      => $this->t("This text is what will appear as your page summary when searching in most search engines"),
            '#maxlength'        => 156,
        ];

        $form['sep2']['#markup'] = '<hr/>';

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type'   => 'submit',
            '#value'  => $this->t("Submit"),
        ];

        return $form;
    }

    /**
     * Validate the alias segment field
     */
    public function validateSegment($element, FormStateInterface $formState)
    {
        $originalValue = trim($formState->getValue('segment'));

        if (!empty($originalValue)) {

            $value = $this->seoService->normalizeSegment($originalValue);

            if ($originalValue !== $value) {
                $formState->setError(
                    $element,
                    $this->t(
                        "The alias '%alias' is invalid and has been normalized to: <strong>%normalized</strong>, please change the value below.",
                        ['%alias' => $originalValue, '%normalized' => $value]
                    )
                );
            }

            $formState->setValueForElement($element, $value);
        }
    }

    public function submitForm(array &$form, FormStateInterface $formState)
    {
        /** @var $node NodeInterface */
        $node = $formState->getTemporaryValue('node');

        if ($this->siteManager->hasContext()) {

            $siteId       = $this->siteManager->getContext()->getId();
            $customAlias  = $formState->getValue('custom_alias');
            $aliasManager = $this->seoService->getAliasManager();

            if (empty($customAlias)) {
                $aliasManager->removeCustomAlias($node->id(), $siteId);
            } else {
                $aliasManager->setCustomAlias($node->id(), $siteId, $customAlias);
            }
        }

        if ($segment = $formState->getValue('segment')) {
            $this->seoService->setNodeSegment($node, $segment);
        } else {
            $this->seoService->setNodeSegment($node, null);
        }

        $this->seoService->setNodeMeta($node, [
            'title'       => $formState->getValue('meta_title'),
            'description' => $formState->getValue('meta_description'),
        ]);
    }
}
