<?php

namespace MakinaCorpus\Ucms\Seo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;

use MakinaCorpus\Ucms\Seo\SeoService;

use Symfony\Component\DependencyInjection\ContainerInterface;

class SeoNodeForm extends FormBase
{
    /**
     * {@inheritdoc}
     */
    static public function create(ContainerInterface $container)
    {
        return new self(
            $container->get('ucms_seo.seo_service')
        );
    }

    /**
     * @var SeoService
     */
    private $seoService;

    public function getFormId()
    {
        return 'ucms_seo_node_form';
    }

    /**
     * Default constructor
     *
     * @param SeoService $seoService
     */
    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
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

        $form['#form_horizontal'] = true;

        // @todo
        //   fetch menu links for this node, in site context, in order to prefix the form field

        $currentAlias = $this->seoService->getNodeSegment($node);

        $form['segment'] = [
            '#title'            => t("Alias"),
            '#type'             => 'textfield',
            '#attributes'       => ['placeholder' => $this->t("about-us")],
            '#default_value'    => $currentAlias,
            '#element_validate' => ['::validateSegment'],
            '#description'      => $this->t("This is the content alias that will be used in order to build URLs for the menu of your site"),
            '#field_prefix'     => 'some/path/', // @todo
        ];

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
        $node = $formState->getTemporaryValue('node');

        if ($segment = $formState->getValue('segment')) {
            $this->seoService->setNodeSegment($node, $segment);
        } else {
            $this->seoService->setNodeSegment($node, null);
        }
    }
}
